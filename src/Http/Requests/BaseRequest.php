<?php

namespace Everware\LaravelCherry\Http\Requests;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Not abstract so can be used as dependency injectable class.
 * @TODO Should this be a trait (and can it be)?
 */
class BaseRequest extends FormRequest
{
    protected bool $validateRouteParams = false;
    public bool $castAll = false;
    public bool $castValidated = false;

    /**
     * Has to be implemented so BaseRequest can be injected just like Request.
     * @return array<string, string|array>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * You can type your closures with a specific type (e.g. `?string $string`).
     * Or, if the key contains '*' with a typed splat operator (e.g. `?string...$strings`).
     *
     * @return array<string,\Closure|string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Add 'sometimes' validation to each field if this is a patch request.
     * @see Validator::validateSometimes() https://laravel.com/docs/9.x/validation#validating-when-present
     */
    public function patchRules(array $rules, bool $force = false): array
    {
        if ($force || $this->isMethod('PATCH')) {
            foreach ($rules as $key => $rule) {
                $rules[$key] = is_string($rule)
                ? 'sometimes|' . $rule
                : array_merge(['sometimes'], $rule);
            }
        }

        return $rules;
    }

    protected array $castCache = [];
    protected function cast(array &$array, bool $fresh = false): array
    {
        if ($fresh) {
            $this->castCache = [];
        }

        if (empty($array)) {
            return $array;
        }

        $casts = $this->casts();

        // Only use $caster for string casts.
        // Note we are using the Model HasAttributes to cast the posted data,
        // but the system used to validate that data differs from HasAttributes.
        $caster = new class($casts) {
            use HasAttributes { castAttribute as public; isCustomDateTimeCast as public; isImmutableCustomDateTimeCast as public; }
            public function __construct(array $casts) { $this->casts = $casts; }
            public function getIncrementing(): bool { return false; } /** @see HasAttributes::getCasts() */
            public function isPrimitiveCast(string $castType): bool { return in_array($castType, static::$primitiveCastTypes); }
        };

        foreach ($casts as $key => $cast) {
            if (array_key_exists($key, $this->castCache)) {
                continue;
            }

            $failedValidation = data_get($this->getValidatorInstance()->failed(), $key);
            if ($failedValidation) {
                $this->castCache[$key] = null;
                continue;
            }

            // We have to determine for every cast whether it matches any keys in $array,
            // because the casts could be wildcarded using '*', so an array intersect does not suffice.
            $value = data_get($array, $key) ?? data_get($array, "{$key}_id");
            if (blank($value)) {
                $this->castCache[$key] = null;
                continue;
            }

            $this->castCache[$key] = match(true) {
                /** @NOTE If you use "date", that's seen as a callable because of the {@see date()} function in PHP.
                  * Either add format (e.g. "date:!Y-m-d") or use "datetime". "datetime" also sets date to start of day,
                  * just like "date" when date is formatted like Y-m-d {@see HasAttributes::asDateTime()}. */
                is_callable($cast) => $cast($value),
                // If you want to retrieve the Model by a different column the primary key,
                // you should just use a closure instead of a class string.
                $cast instanceof Model || class_exists($cast) && !enum_exists($cast) && !$caster->isPrimitiveCast($cast)
                    // findOrFail() does findMany when $value is an array.
                    // Note this failed-validation-rule check does not work if the cast is used inside a validation-rule
                    // and that rule is checked before the rule that validates the casted property.
                    => (function() use($key, $cast, &$value) {
                        $result = $cast::query()->findOrFail($value);
                        if ($value instanceof Arrayable) {
                            $value = $value->toArray();
                        }
                        if (!is_array($value)) {
                            return $result;
                        }
                        /** @var \Illuminate\Database\Eloquent\Collection $result
                          * We do this because findOrFail does array_unique, and our arrays don't have to have unique values. */
                        $result = $result->keyBy(fn($m)=> $m->getKey());
                        $return = $result->chunk(0)->concat($value)->map(fn($id)=> $result[$id]);
                        return $return;
                    })(),
                default
                    => $caster->setDateFormat($caster->isCustomDateTimeCast($cast) || $caster->isImmutableCustomDateTimeCast($cast) ? explode(':', $cast, 2)[1] : null)
                    /** Also @see HasAttributes::addCastAttributesToArray() */
                    ->castAttribute($key, $value),
            };

            if (str_contains($key, '*')) {
                \HArr::overwriteWildcards($array, $key, $this->castCache[$key]);
            } else {
                data_set($array, $key, $this->castCache[$key], overwrite: true);
            }
        }

        return $array;
    }

    /**
     * Called from @see FormRequest::createDefaultValidator()
     * @inheritDoc
     */
    public function validationData()
    {
        // Return parent::all() instead of $this->all() so the values do not get casted.
        $all = parent::all();

        if ($this->validateRouteParams) {
            /** Based on @see BaseRequest::all() */
            $all = array_merge($this->route()->parameters(), $all);
        }

        return $all;
    }

    /**
     * @inheritDoc
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();

        if ($this->castValidated) {
            $this->cast($validated);
        }

        return data_get($validated, $key, $default);
    }

    /**
     * @inheritDoc
     */
    public function all($keys = null)
    {
        $all = parent::all(...func_get_args());
        return $this->castAll ? $this->cast($all) : $all;
    }

    /**
     * Also called by @see Request::offsetGet()
     * @inheritDoc
     */
    public function __get($key)
    {
        // If keys don't exist they aren't added to result array, as opposed to `all(keys)`.
        $data = \Arr::only(parent::all(), [$key, "{$key}_id"]); // `parent::` so not casted.
        $this->cast($data);
        /** Overwrites {@see parent::__get()} functionality so only requested properties get casted. */
        $value = \Arr::get($data, $key, fn()=> $this->route($key));
        return $value;
    }

    /**
     * Create a new BaseRequest (static) from another Request.
     * Based on @see FormRequestServiceProvider::boot()
     * Based on @see Request::capture()
     *
     * @param Request $from
     * @param array|null $replace
     * @param array|null $merge
     * @return static
     */
    public static function createFromCustom(Request $from, ?array $replace = null, ?array $merge = null): static
    {
        $request = parent::createFrom($from, null);
        // Fix bug in Laravel with statement `$request->setJson($from->json());`,
        // which sets the new request to a referenced ParameterBag instead of a cloned instance of that bag.
        $request->setJson(new ParameterBag($request->json()->all()));

        if ($replace !== null) {
            $request->replace($replace);
        }
        if ($merge !== null) {
            $request->merge($merge);
        }

        $request->setContainer($from->container ?? app());
        $request->setRedirector($from->redirector ?? redirect());

        $request->validateResolved();

        return $request;
    }
}
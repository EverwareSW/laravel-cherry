
job:dispatch and fromParams

FailedToSentry


CherryEnum

Helpers and \Helper

base request with casting

        //TODO show index request
        $repo = app(ModelRepository::class, ['model' => Workout::class]);
        $data = $repo->paginate($request);
        // With Laravel Data:
        return WorkoutResponseData::collect($data, PaginatedDataCollection::class);

ActionPermisionProtection

RolePermissionSeeder

TestCaseHelper
PestExpectations

Mail testing
* \Mail::assertSent(OfferSendMail::class, 1);
* $mailable = \Mail::shiftSentMailable();
* $mailable->assertHasTo('example@email.com');
* $mailable->assertHasBcc('example@email.com');
* $mailable->assertHasSubject("Example subject");
* $mailable->assertSeeInHtml("Some text in HTML", false);
* $mailable->assertHasAttachedData(fn($attachment)=> true)

LazyCollection fix


// `composer test`
// `vendor/bin/testbench whatever-artisan-command`


# Snapshot testing
To be able to refresh the snapshot for the last test with a failed snapshot assertion, add this to phpunit.xml (directly within `<phpunit>`):
```xml
    <!-- See https://docs.phpunit.de/en/12.4/extending-phpunit.html#registering-an-extension-from-a-composer-package -->
    <extensions>
        <bootstrap class="Everware\LaravelCherry\Tests\PHPUnitExtension" />
    </extensions>
```
You can then run
```bash
php artisan test:resnap
```
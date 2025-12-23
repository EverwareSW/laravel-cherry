
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
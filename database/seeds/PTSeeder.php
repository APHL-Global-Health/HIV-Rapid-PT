<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Permission;
use App\Role;
use App\County;
use App\SubCounty;
use App\Program;
use App\Set;
use App\Field;
use App\Option;
use App\Notification;
use App\Questionnaire;
use App\Nonperformance;

//	Carbon - for use with dates
use Jenssegers\Date\Date as Carbon;

class PTSeeder extends DatabaseSeeder
{
    public function run()
    {
    	$now = Carbon::now('Africa/Nairobi');
    	/* Users table */
        $usersData = array(
            array(
                "username" => "admin", "password" => Hash::make("password"), "email" => "admin@rhtpt.or.ke",
                "name" => "PT Administrator", "gender" => "1", "phone"=>"0722000000", "address" => "P.O. Box 59857-00200, Nairobi", "created_at" => $now, "updated_at" => $now
            ),
            array(
                "username" => "jbundi", "password" => Hash::make("password"), "email" => "jeffbundi@gmail.com",
                "name" => "Jeff Bundi", "gender" => "0", "phone"=>"0721825737", "address" => "Nairobi", "created_at" => $now, "updated_at" => $now
            )
        );
        foreach ($usersData as $user)
        {
            $users[] = User::create($user);
        }
        $this->command->info('Users table seeded');

        /* Permissions table */
        $permissions = array(
            array("name" => "back-up", "display_name" => "Can back up"),

            array("name" => "user-management", "display_name" => "Can manage users"),
            array("name" => "create-user", "display_name" => "Can create user"),
            array("name" => "read-user", "display_name" => "Can read user"),
            array("name" => "update-user", "display_name" => "Can update user"),
            array("name" => "delete-user", "display_name" => "Can delete user"),
            array("name" => "restore-user", "display_name" => "Can restore user"),
            array("name" => "transfer-user", "display_name" => "Can transfer user"),

            array("name" => "create-role", "display_name" => "Can create role"),
            array("name" => "read-role", "display_name" => "Can read role"),
            array("name" => "update-role", "display_name" => "Can update role"),
            array("name" => "delete-role", "display_name" => "Can delete role"),
            array("name" => "restore-role", "display_name" => "Can restore role"),

            array("name" => "read-permission", "display_name" => "Can read permission"),
            array("name" => "assign-role", "display_name" => "Can assign role"),

            array("name" => "facility-catalog", "display_name" => "Can manage facility catalog"),
            array("name" => "create-facility", "display_name" => "Can create facility"),
            array("name" => "read-facility", "display_name" => "Can read facility"),
            array("name" => "update-facility", "display_name" => "Can update facility"),
            array("name" => "delete-facility", "display_name" => "Can delete facility"),

            array("name" => "program-management", "display_name" => "Can manage program"),
            array("name" => "create-set", "display_name" => "Can create field set"),
            array("name" => "read-set", "display_name" => "Can read field set"),
            array("name" => "view-set", "display_name" => "Can view field set"),
            array("name" => "update-set", "display_name" => "Can update field set"),
            array("name" => "restore-set", "display_name" => "Can restore field set"),
            array("name" => "delete-set", "display_name" => "Can delete field set"),
            array("name" => "create-field", "display_name" => "Can create field"),
            array("name" => "read-field", "display_name" => "Can read field"),
            array("name" => "view-field", "display_name" => "Can view field"),
            array("name" => "update-field", "display_name" => "Can update field"),
            array("name" => "delete-field", "display_name" => "Can delete field"),
            array("name" => "create-option", "display_name" => "Can create option"),
            array("name" => "read-option", "display_name" => "Can read option"),
            array("name" => "view-option", "display_name" => "Can view option"),
            array("name" => "update-option", "display_name" => "Can update option"),
            array("name" => "delete-option", "display_name" => "Can delete option"),
            array("name" => "create-nonperf", "display_name" => "Can create non-performance"),
            array("name" => "read-nonperf", "display_name" => "Can read non-performance"),
            array("name" => "view-nonperf", "display_name" => "Can view non-performance"),
            array("name" => "update-nonperf", "display_name" => "Can update non-performance"),
            array("name" => "delete-nonperf", "display_name" => "Can delete non-performance"),

            array("name" => "create-notification", "display_name" => "Can create option"),
            array("name" => "read-notification", "display_name" => "Can read notification"),
            array("name" => "view-notification", "display_name" => "Can view notification"),
            array("name" => "update-notification", "display_name" => "Can update notification"),
            array("name" => "delete-notification", "display_name" => "Can delete notification"),

            array("name" => "proficiency-testing", "display_name" => "Can manage proficiency testing"),
            array("name" => "create-program", "display_name" => "Can create program"),
            array("name" => "read-program", "display_name" => "Can read program"),
            array("name" => "view-program", "display_name" => "Can view program"),
            array("name" => "update-program", "display_name" => "Can update program"),
            array("name" => "delete-program", "display_name" => "Can delete program"),
            array("name" => "restore-program", "display_name" => "Can restore program"),
            array("name" => "create-shipper", "display_name" => "Can create shipper"),
            array("name" => "read-shipper", "display_name" => "Can read shipper"),
            array("name" => "view-shipper", "display_name" => "Can view shipper"),
            array("name" => "update-shipper", "display_name" => "Can update shipper"),
            array("name" => "delete-shipper", "display_name" => "Can delete shipper"),
            array("name" => "restore-shipper", "display_name" => "Can restore shipper"),
            array("name" => "create-sample", "display_name" => "Can create sample"),
            array("name" => "read-sample", "display_name" => "Can read sample"),
            array("name" => "view-sample", "display_name" => "Can view sample"),
            array("name" => "update-sample", "display_name" => "Can update sample"),
            array("name" => "delete-sample", "display_name" => "Can delete sample"),
            array("name" => "create-round", "display_name" => "Can create round"),
            array("name" => "read-round", "display_name" => "Can read round"),
            array("name" => "view-round", "display_name" => "Can view round"),
            array("name" => "update-round", "display_name" => "Can update round"),
            array("name" => "delete-round", "display_name" => "Can delete round"),
            array("name" => "restore-round", "display_name" => "Can restore round"),
            array("name" => "create-lot", "display_name" => "Can create lot"),
            array("name" => "read-lot", "display_name" => "Can read lot"),
            array("name" => "view-lot", "display_name" => "Can view lot"),
            array("name" => "update-lot", "display_name" => "Can update lot"),
            array("name" => "delete-lot", "display_name" => "Can delete lot"),
            array("name" => "create-panel", "display_name" => "Can create panel"),
            array("name" => "read-panel", "display_name" => "Can read panel"),
            array("name" => "view-panel", "display_name" => "Can view panel"),
            array("name" => "update-panel", "display_name" => "Can update panel"),
            array("name" => "delete-panel", "display_name" => "Can delete panel"),
            array("name" => "create-shipment", "display_name" => "Can create shipment"),
            array("name" => "read-shipment", "display_name" => "Can read shipment"),
            array("name" => "view-shipment", "display_name" => "Can view shipment"),
            array("name" => "update-shipment", "display_name" => "Can update shipment"),
            array("name" => "delete-shipment", "display_name" => "Can delete shipment"),
            array("name" => "receive-shipment", "display_name" => "Can receive shipment"),
            array("name" => "distribute-shipment", "display_name" => "Can distribute shipment"),
            array("name" => "view-distribution", "display_name" => "Can view picked shipments"),
            array("name" => "enrol-participants", "display_name" => "Can enrol participants"),
            array("name" => "view-participants", "display_name" => "Can view enrolled participants"),
            array("name" => "create-result", "display_name" => "Can create result"),
            array("name" => "read-result", "display_name" => "Can read result"),
            array("name" => "view-result", "display_name" => "Can view result"),
            array("name" => "update-result", "display_name" => "Can update result"),
            array("name" => "delete-result", "display_name" => "Can delete result"),
            array("name" => "verify-result", "display_name" => "Can verify result"),

            array("name" => "bulk-sms", "display_name" => "Can manage bulk SMS"),
            array("name" => "lot", "display_name" => "Can manage lots"),
            array("name" => "config", "display_name" => "Can manage Configurations"),
            array("name" => "view-report", "display_name" => "Can view report"),
            array("name" => "export-report", "display_name" => "Can export report")
        );
        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
        $this->command->info('Permissions table seeded');
        /* Roles table */
        $roles = array(
            array("name" => "Superadmin", "display_name" => "Overall Administrator"),
            array("name" => "Participant", "display_name" => "Participant"),
            array("name" => "Partner", "display_name" => "Partner"),
            array("name" => "County Coordinator", "display_name" => "County Coordinator"),
            array("name" => "Program Manager", "display_name" => "Program Manager"),
            array("name" => "Facility Incharge", "display_name" => "Facility Incharge"),
            array("name" => "Sub-County Coordinator", "display_name" => "Sub-County Coordinator")
        );
        foreach ($roles as $role)
        {
            Role::create($role);
        }
        $this->command->info('Roles table seeded');

        $role1 = Role::find(1);
        $permissions = Permission::all();

        //Assign all permissions to role administrator
        foreach ($permissions as $permission)
        {
            $role1->attachPermission($permission);
        }
        //Assign role Superadmin to all permissions
        User::find(1)->attachRole($role1);

        /* Programs table */
        $programs = array(
            array("name" => "Lab", "description" => "Laboratory"),
            array("name" => "PMTCT", "description" => "Prevention of Mother To Child Transmission of HIV/AIDS"),
            array("name" => "PSC/CCC", "description" => "Patient Support Center"),
            array("name" => "VCT", "description" => "Voluntary Counselling and Testing"),
            array("name" => "VMMC", "description" => "Voluntary Male Medical Circumcision"),
            array("name" => "PITC", "description" => "Patient Information and Testing Center"),
            array("name" => "HBTC", "description" => "Home-Based Testing and Counselling")
        );
        foreach ($programs as $program)
        {
            Program::create($program);
        }
        $this->command->info('Programs table seeded');

        /* Counties table */
        $counties = array(
            array("name" => "Mombasa"),
            array("name" => "Kwale"),
            array("name" => "Kilifi"),
            array("name" => "Tana River"),
            array("name" => "Lamu"),
            array("name" => "Taita Taveta"),
            array("name" => "Garissa"),
            array("name" => "Wajir"),
            array("name" => "Mandera"),
            array("name" => "Marsabit"),
            array("name" => "Isiolo"),
            array("name" => "Meru"),
            array("name" => "Tharaka Nithi"),
            array("name" => "Embu"),
            array("name" => "Kitui"),
            array("name" => "Machakos"),
            array("name" => "Makueni"),
            array("name" => "Nyandarua"),
            array("name" => "Nyeri"),
            array("name" => "Kirinyaga"),
            array("name" => "Murang'a"),
            array("name" => "Kiambu"),
            array("name" => "Turkana"),
            array("name" => "West Pokot"),
            array("name" => "Samburu"),
            array("name" => "Trans Nzoia"),
            array("name" => "Uasin Gishu"),
            array("name" => "Elgeyo Marakwet"),
            array("name" => "Nandi"),
            array("name" => "Baringo"),
            array("name" => "Laikipia"),
            array("name" => "Nakuru"),
            array("name" => "Narok"),
            array("name" => "Kajiado"),
            array("name" => "Kericho"),
            array("name" => "Bomet"),
            array("name" => "Kakamega"),
            array("name" => "Vihiga"),
            array("name" => "Bungoma"),
            array("name" => "Busia"),
            array("name" => "Siaya"),
            array("name" => "Kisumu"),
            array("name" => "Homa Bay"),
            array("name" => "Migori"),
            array("name" => "Kisii"),
            array("name" => "Nyamira"),
            array("name" => "Nairobi")
        );
        foreach ($counties as $county)
        {
            County::create($county);
        }
        $this->command->info('Counties table seeded');

        /* Notifications table */
        $notifications = array(
            array("template" => Notification::ENROLMENT, "message" => "Dear PT Participant, you have been enrolled into [round] of PT. If not not participating, contact NPHL on 0722934622 or NPHLpt@gmail.com"),
            array("template" => Notification::PANEL_DISPATCH, "message" => "Dear PT Participant, NPHL has dispatched your PT Panel for [round]. If not received within 7 days, contact NPHL on 0722934622 or NPHLpt@gmail.com"),
            array("template" => Notification::RESULTS_RECEIVED, "message" => "Dear PT Participant, NPHL has received your PT Results for [round]. You will get your feedback shortly."),
            array("template" => Notification::FEEDBACK_RELEASE, "message" => "Dear PT Participant, NPHL has released your PT Feedback for [round]. If not received within 7 days, contact NPHL on 0722934622 or NPHLpt@gmail.com"),            
        );
        foreach ($notifications as $notification)
        {
            Notification::create($notification);
        }
        $this->command->info('Notifications table seeded');
        /* Options table */
        $options = array(
            array("title" => "KHB", "description" => ""),
            array("title" => "First Response", "description" => ""),
            array("title" => "Unigold", "description" => ""),
            array("title" => "Other", "description" => ""),
            array("title" => "Reactive", "description" => ""),
            array("title" => "Non Reactive", "description" => ""),
            array("title" => "Invalid", "description" => ""),
            array("title" => "Not Done", "description" => ""),
            array("title" => "Positive", "description" => ""),
            array("title" => "Negative", "description" => ""),
            array("title" => "Indeterminate", "description" => "")
        );
        foreach ($options as $option)
        {
            Option::create($option);
        }
        $this->command->info('Options table seeded');
        /* Field sets table */
        $sets = array(
            array("title" => "PT Panel Dates", "description" => "", "order" => "0"),
            array("title" => "Test 1", "description" => "", "order" => "1"),
            array("title" => "Test 2", "description" => "", "order" => "2"),
            array("title" => "Test 3", "description" => "", "order" => "3"),
            array("title" => "PT Panel 1", "description" => "Test Results", "order" => "4"),
            array("title" => "PT Panel 2", "description" => "Test Results", "order" => "5"),
            array("title" => "PT Panel 3", "description" => "Test Results", "order" => "6"),
            array("title" => "PT Panel 4", "description" => "Test Results", "order" => "7"),
            array("title" => "PT Panel 5", "description" => "Test Results", "order" => "8"),
            array("title" => "PT Panel 6", "description" => "Test Results", "order" => "9"),
            array("title" => "Remarks", "description" => "Remarks", "order" => "10"),
        );
        foreach ($sets as $set)
        {
            Set::create($set);
        }
        $this->command->info('Field Sets table seeded');
        /* Dummy bulk sms settings */
        DB::table('bulk_sms_settings')->insert(array("code" => "talking", "username" => "africa", "api_key" => "LBD239F81V", "created_at" => $now, "updated_at" => $now));

        $this->command->info('Bulk SMS Settings table seeded');
        /* Non-performance table */
        $reasons = array(
            array("title" => "Transferred", "description" => ""),
            array("title" => "Resigned", "description" => ""),
            array("title" => "On Leave", "description" => ""),
            array("title" => "Off Duty", "description" => ""),
            array("title" => "Deceased", "description" => ""),
            array("title" => "Other", "description" => "")
        );
        foreach ($reasons as $reason)
        {
            Nonperformance::create($reason);
        }
        $this->command->info('Non-performance table seeded');
    }
}

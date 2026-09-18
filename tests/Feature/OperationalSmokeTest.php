<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OperationalSmokeTest extends TestCase
{
    public function test_security_headers_are_present_for_guests(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_required_operational_routes_are_registered(): void
    {
        foreach ([
            'reports.export',
            'recruitment.candidates.show',
            'performance.kpis.store',
            'training.participants.attendance',
            'assets.maintenance.store',
            'business-trips.settle',
            'announcements.read',
        ] as $name) {
            $this->assertNotNull(Route::getRoutes()->getByName($name), $name.' route is missing.');
        }
    }

    public function test_legacy_scheduler_tasks_are_registered(): void
    {
        Artisan::call('schedule:list');
        $commands = Artisan::output();

        $this->assertStringContainsString('hris:legacy-reminders training', $commands);
        $this->assertStringContainsString('hris:legacy-reminders birthday', $commands);
        $this->assertStringContainsString('hris:legacy-reminders contract', $commands);
        $this->assertStringContainsString('hris:legacy-reminders cleanup', $commands);
    }
}

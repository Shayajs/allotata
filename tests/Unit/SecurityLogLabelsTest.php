<?php

namespace Tests\Unit;

use App\Models\SecurityLog;
use PHPUnit\Framework\TestCase;

class SecurityLogLabelsTest extends TestCase
{
    public function test_admin_account_event_labels(): void
    {
        $this->assertSame(
            'Consultation admin (lecture seule)',
            SecurityLog::labelForEvent('admin_account_access_view')
        );
        $this->assertSame(
            'Accès admin (mode édition)',
            SecurityLog::labelForEvent('admin_account_access_edit')
        );
        $this->assertSame(
            'Accès admin (mode support)',
            SecurityLog::labelForEvent('admin_account_access_support')
        );
        $this->assertSame(
            'Accès admin (mode facturation)',
            SecurityLog::labelForEvent('admin_account_access_billing')
        );
        $this->assertSame(
            'Action admin sur votre compte',
            SecurityLog::labelForEvent('admin_account_action')
        );
    }
}

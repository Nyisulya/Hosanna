<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\DepartmentController;

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'sw'])) {
        session(['locale' => $locale]);
    }
    return back();
})->name('lang.switch');



Route::get('/', function () {
    return redirect()->route('login');
});

use App\Http\Controllers\Auth\LoginController;

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1'); // 5 attempts per minute
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

use App\Http\Controllers\Auth\ForgotPasswordController;
Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');

// Language switching route
Route::get('language/{locale}', [App\Http\Controllers\LanguageController::class, 'switch'])->name('language.switch');

use App\Http\Controllers\Auth\RegisterController;

// Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
// Route::post('register', [RegisterController::class, 'register']);

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard')->middleware(['auth']);

// Profile routes
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [App\Http\Controllers\ProfileController::class, 'showChangePasswordForm'])->name('profile.change-password.form');
    Route::post('/profile/change-password', [App\Http\Controllers\ProfileController::class, 'changePassword'])->name('profile.change-password');
    Route::get('/profile/qr-download', [App\Http\Controllers\ProfileController::class, 'downloadQr'])->name('profile.qr-download');
    
    // Inbox routes
    Route::get('/inbox', [App\Http\Controllers\InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{id}', [App\Http\Controllers\InboxController::class, 'show'])->name('inbox.show');
});

Route::resource('assets', App\Http\Controllers\AssetController::class)->middleware(['auth']);

// Roster Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/rosters', [App\Http\Controllers\RosterController::class, 'index'])->name('rosters.index');
    Route::post('/rosters', [App\Http\Controllers\RosterController::class, 'store'])->name('rosters.store');
    Route::delete('/rosters/{roster}', [App\Http\Controllers\RosterController::class, 'destroy'])->name('rosters.destroy');
    Route::get('/my-roster', [App\Http\Controllers\RosterController::class, 'myRoster'])->name('rosters.my');
});

// Prayer Wall Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/prayer-wall', [App\Http\Controllers\PrayerRequestController::class, 'wall'])->name('prayer-wall.index');
    Route::post('/prayer-wall', [App\Http\Controllers\PrayerRequestController::class, 'store'])->name('prayer-wall.store');
    Route::post('/prayer-wall/{prayer}/pray', [App\Http\Controllers\PrayerRequestController::class, 'incrementPrayer'])->name('prayer-wall.pray');
    Route::get('/my-prayer-requests', [App\Http\Controllers\PrayerRequestController::class, 'myRequests'])->name('prayer-wall.my-requests');
    Route::put('/prayer-wall/{prayer}/mark-answered', [App\Http\Controllers\PrayerRequestController::class, 'markAnswered'])->name('prayer-wall.mark-answered');
});

Route::resource('departments', App\Http\Controllers\DepartmentController::class)->middleware(['auth']);
Route::post('departments/{department}/announcements', [App\Http\Controllers\DepartmentController::class, 'storeAnnouncement'])
    ->name('departments.announcements.store')
    ->middleware(['auth']);


Route::middleware(['auth'])->group(function () {
    // Zone / Kanda Routes
    Route::resource('small-groups', App\Http\Controllers\SmallGroupController::class);
    Route::get('/zones', [App\Http\Controllers\SmallGroupController::class, 'index'])->name('zones.index');
    Route::post('/small-groups/{smallGroup}/add-member', [App\Http\Controllers\SmallGroupController::class, 'addMember'])->name('small-groups.add-member');
    Route::delete('/small-groups/{smallGroup}/remove-member/{member}', [App\Http\Controllers\SmallGroupController::class, 'removeMember'])->name('small-groups.remove-member');
    Route::post('/small-groups/{smallGroup}/meetings', [App\Http\Controllers\SmallGroupController::class, 'storeMeeting'])->name('small-groups.store-meeting');
    Route::post('/small-groups/meetings/{meeting}/send-reminder', [App\Http\Controllers\SmallGroupController::class, 'sendMeetingReminder'])->name('small-groups.meetings.send-reminder');
    Route::get('/small-groups/meetings/{meeting}/attendance', [App\Http\Controllers\SmallGroupController::class, 'meetingAttendance'])->name('small-groups.meetings.attendance');
    Route::post('/small-groups/meetings/{meeting}/attendance', [App\Http\Controllers\SmallGroupController::class, 'saveMeetingAttendance'])->name('small-groups.meetings.save-attendance');
    Route::get('/my-small-group', [App\Http\Controllers\SmallGroupController::class, 'myGroup'])->name('small-groups.my-group');
    Route::get('/my-zone', [App\Http\Controllers\SmallGroupController::class, 'myGroup'])->name('zones.my-group');
    
    // Zone Attendance Tracking
    Route::get('/my-small-group/attendance', [App\Http\Controllers\SmallGroupController::class, 'groupAttendance'])->name('small-groups.attendance');
    Route::get('/my-zone/attendance', [App\Http\Controllers\SmallGroupController::class, 'groupAttendance'])->name('zones.attendance');
    Route::post('/my-small-group/attendance/mark', [App\Http\Controllers\SmallGroupController::class, 'markGroupAttendance'])->name('small-groups.attendance.mark');
    Route::post('/my-small-group/attendance/bulk-mark', [App\Http\Controllers\SmallGroupController::class, 'bulkMarkGroupAttendance'])->name('small-groups.attendance.bulk-mark');
    
    // Redirect legacy weekly report paths to small-groups
    Route::any('/weekly-reports/{any?}', function () {
        return redirect()->route('small-groups.index');
    })->where('any', '.*');
    Route::any('/small-groups-admin/questions/{any?}', function () {
        return redirect()->route('small-groups.index');
    })->where('any', '.*');
    
    // Zone Finance & Communication
    Route::post('/small-groups/{smallGroup}/finance/offering', [App\Http\Controllers\SmallGroupFinanceController::class, 'storeOffering'])->name('small-groups.finance.store-offering');
    Route::get('/small-groups/finance/{offering}', [App\Http\Controllers\SmallGroupFinanceController::class, 'showOffering'])->name('small-groups.finance.show');
    Route::post('/small-groups/finance/{offering}/payment', [App\Http\Controllers\SmallGroupFinanceController::class, 'storePayment'])->name('small-groups.finance.store-payment');
    Route::post('/small-groups/{smallGroup}/communication/remind-pending', [App\Http\Controllers\SmallGroupCommunicationController::class, 'remindPendingReporters'])->name('small-groups.communication.remind-pending');
    Route::post('/small-groups/finance/{offering}/remind-debtors', [App\Http\Controllers\SmallGroupCommunicationController::class, 'remindDebtors'])->name('small-groups.communication.remind-debtors');

    // Role & Permission Management
    Route::resource('roles', App\Http\Controllers\RoleController::class);

    // Leader Management
    Route::get('leaders/export', [App\Http\Controllers\LeaderController::class, 'export'])->name('leaders.export');
    Route::resource('leaders', App\Http\Controllers\LeaderController::class);
    Route::post('/leaders/remove', [App\Http\Controllers\LeaderController::class, 'remove'])->name('leaders.remove');
    
    
    // Books (Spirit of Prophecy Library)
    Route::get('books/admin', [App\Http\Controllers\BookController::class, 'adminIndex'])->name('books.admin');
    Route::resource('books', App\Http\Controllers\BookController::class)->except(['edit', 'update']);
    
    // Birthdays & Anniversaries
    Route::get('birthdays', [App\Http\Controllers\BirthdayController::class, 'index'])->name('birthdays.index');
    Route::get('anniversaries', [App\Http\Controllers\BirthdayController::class, 'anniversaries'])->name('anniversaries.index');
    Route::post('birthdays/{member}/greet', [App\Http\Controllers\BirthdayController::class, 'sendGreeting'])->name('birthdays.sendGreeting');

    // Prayer Request System
    Route::get('prayer-wall', [App\Http\Controllers\PrayerRequestController::class, 'wall'])->name('prayer-requests.wall');
    Route::get('prayer-requests/my', [App\Http\Controllers\PrayerRequestController::class, 'myRequests'])->name('prayer-requests.my');
    Route::post('prayer-requests', [App\Http\Controllers\PrayerRequestController::class, 'store'])->name('prayer-requests.store');
    Route::post('prayer-requests/{prayer}/pray', [App\Http\Controllers\PrayerRequestController::class, 'incrementPrayer'])->name('prayer-requests.pray');
    Route::put('prayer-requests/{prayer}/answer', [App\Http\Controllers\PrayerRequestController::class, 'markAnswered'])->name('prayer-requests.mark-answered');

    // Visitor Tracking
    Route::resource('visitors', App\Http\Controllers\VisitorController::class);
});

// Giving Categories Routes (Admin only)
Route::middleware(['auth'])->group(function () {
    Route::get('/giving-categories', [App\Http\Controllers\GivingCategoryController::class, 'index'])->name('giving-categories.index');
    Route::post('/giving-categories', [App\Http\Controllers\GivingCategoryController::class, 'store'])->name('giving-categories.store');
    Route::put('/giving-categories/{givingCategory}', [App\Http\Controllers\GivingCategoryController::class, 'update'])->name('giving-categories.update');
    Route::delete('/giving-categories/{givingCategory}', [App\Http\Controllers\GivingCategoryController::class, 'destroy'])->name('giving-categories.destroy');
    Route::patch('/giving-categories/{givingCategory}/toggle', [App\Http\Controllers\GivingCategoryController::class, 'toggleStatus'])->name('giving-categories.toggle');
});

// Church Announcements Routes (Matangazo)
Route::middleware(['auth'])->group(function () {
    Route::get('/announcements/current', [App\Http\Controllers\ChurchAnnouncementController::class, 'current'])->name('announcements.current');
    Route::get('/announcements/view', [App\Http\Controllers\ChurchAnnouncementController::class, 'memberView'])->name('announcements.member-view');
    Route::resource('announcements', App\Http\Controllers\ChurchAnnouncementController::class);
});


Route::resource('users', App\Http\Controllers\UserController::class)->middleware(['auth']);
Route::get('/attendance/scanner', App\Livewire\AttendanceScanner::class)->name('attendance.scanner')->middleware(['auth']);
Route::get('/attendance/scan', App\Livewire\AttendanceScanner::class)->name('attendance.scan')->middleware(['auth']);
Route::get('/attendance/scan-qr/{memberNumber}', [App\Http\Controllers\AttendanceController::class, 'scanQr'])->name('attendance.scan-qr');
Route::post('/attendance/scan-qr/{memberNumber}', [App\Http\Controllers\AttendanceController::class, 'scanQrLogin'])->name('attendance.scan-qr.login');
Route::middleware(['auth'])->prefix('attendance')->group(function () {
    Route::get('/', [App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/record', [App\Http\Controllers\AttendanceController::class, 'recordManual'])->name('attendance.record');
    Route::get('/my-attendance', [App\Http\Controllers\AttendanceController::class, 'myAttendance'])->name('attendance.my-attendance');
    Route::get('/events/{event}', [App\Http\Controllers\AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/events/{event}/mark', [App\Http\Controllers\AttendanceController::class, 'markAttendance'])->name('attendance.mark');
    Route::post('/events/{event}/bulk-mark', [App\Http\Controllers\AttendanceController::class, 'bulkMark'])->name('attendance.bulk-mark');
});
Route::resource('departments', DepartmentController::class)->only(['index', 'show'])->middleware(['auth']);
Route::get('events/calendar', [App\Http\Controllers\EventController::class, 'calendar'])->name('events.calendar')->middleware(['auth']);
Route::post('events/{event}/register', [App\Http\Controllers\EventController::class, 'register'])->name('events.register')->middleware(['auth']);
Route::resource('events', App\Http\Controllers\EventController::class)->middleware(['auth']);
Route::get('/contributions/{contribution}/download', [App\Http\Controllers\ContributionController::class, 'downloadReceipt'])->name('contributions.download')->middleware(['auth']);
Route::resource('contributions', App\Http\Controllers\ContributionController::class)->middleware(['auth']);
Route::middleware(['auth'])->prefix('pledges')->group(function () {
    Route::get('/', [App\Http\Controllers\PledgeController::class, 'index'])->name('pledges.index');
    Route::get('/create', [App\Http\Controllers\PledgeController::class, 'create'])->name('pledges.create');
    Route::post('/', [App\Http\Controllers\PledgeController::class, 'store'])->name('pledges.store');
    Route::get('/{pledge}', [App\Http\Controllers\PledgeController::class, 'show'])->name('pledges.show');
    Route::post('/{pledge}/payment', [App\Http\Controllers\PledgeController::class, 'makePayment'])->name('pledges.payment');
});
Route::resource('projects', App\Http\Controllers\ProjectController::class)->middleware(['auth']);
Route::post('/projects/{project}/group-goals', [App\Http\Controllers\ProjectController::class, 'updateGroupGoals'])->name('projects.update-group-goals')->middleware(['auth']);
Route::get('/members/{member}/id-card', [App\Http\Controllers\IdCardController::class, 'show'])->name('members.id-card');
Route::get('/members/{member}/id-card/download', [App\Http\Controllers\IdCardController::class, 'download'])->name('members.id-card.download');

Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/calendar', [App\Http\Controllers\CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [App\Http\Controllers\CalendarController::class, 'events'])->name('calendar.events');
    Route::get('/communication', [App\Http\Controllers\CommunicationController::class, 'index'])->name('communication.index');
    Route::post('/communication/send', [App\Http\Controllers\CommunicationController::class, 'send'])->name('communication.send');
    Route::post('/communication/saturday-reminder', [App\Http\Controllers\CommunicationController::class, 'sendSaturdayReminderNow'])->name('communication.saturday-reminder');
    Route::get('/dashboard', [App\Http\Controllers\ReportController::class, 'dashboard'])->name('dashboard');
});

Route::prefix('financial')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\FinancialController::class, 'dashboard'])->name('financial.dashboard');
    Route::get('/transactions', [App\Http\Controllers\FinancialController::class, 'transactions'])->name('financial.transactions');
    Route::get('/income/create', [App\Http\Controllers\FinancialController::class, 'createIncome'])->name('financial.income.create');
    Route::post('/income', [App\Http\Controllers\FinancialController::class, 'storeIncome'])->name('financial.income.store');
    Route::get('/expense/create', [App\Http\Controllers\FinancialController::class, 'createExpense'])->name('financial.expense.create');
    Route::post('/expense', [App\Http\Controllers\FinancialController::class, 'storeExpense'])->name('financial.expense.store');
    Route::get('/pledges', [App\Http\Controllers\FinancialController::class, 'pledges'])->name('financial.pledges');
    Route::post('/pledges', [App\Http\Controllers\FinancialController::class, 'storePledge'])->name('financial.pledges.store');
    Route::post('/pledges/{pledge}/payment', [App\Http\Controllers\FinancialController::class, 'recordPledgePayment'])->name('financial.pledges.payment');
    Route::get('/reports', [App\Http\Controllers\FinancialController::class, 'reports'])->name('financial.reports');
});

// Online Giving routes (HarakaPay Mobile Money / Pesapal v3)
Route::get('/give', [App\Http\Controllers\PaymentController::class, 'showForm'])->name('give.form')->middleware(['auth']);
Route::post('/give', [App\Http\Controllers\PaymentController::class, 'process'])->name('give.process')->middleware(['auth']);
Route::get('/give/waiting/{payment}', [App\Http\Controllers\PaymentController::class, 'waiting'])->name('give.waiting')->middleware(['auth']);
Route::get('/give/status/{payment}', [App\Http\Controllers\PaymentController::class, 'checkStatus'])->name('give.status')->middleware(['auth']);
Route::get('/give/success', [App\Http\Controllers\PaymentController::class, 'success'])->name('give.success')->middleware(['auth']);
Route::get('/pesapal/ipn', [App\Http\Controllers\PaymentController::class, 'webhook'])->name('pesapal.ipn');
Route::match(['get', 'post'], '/harakapay/callback', [App\Http\Controllers\PaymentController::class, 'harakapayCallback'])->name('harakapay.callback');

// Celebration routes
Route::prefix('celebrations')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\CelebrationController::class, 'dashboard'])->name('celebrations.dashboard');
    Route::get('/birthdays', [App\Http\Controllers\CelebrationController::class, 'birthdays'])->name('celebrations.birthdays');
    Route::get('/anniversaries', [App\Http\Controllers\CelebrationController::class, 'anniversaries'])->name('celebrations.anniversaries');
});

// Pastoral Care routes
Route::prefix('pastoral-care')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\PastoralCareController::class, 'dashboard'])->name('pastoral-care.dashboard');
    Route::get('/visits', [App\Http\Controllers\PastoralCareController::class, 'visits'])->name('pastoral-care.visits');
    Route::post('/visits', [App\Http\Controllers\PastoralCareController::class, 'storeVisit'])->name('pastoral-care.visits.store');
    Route::get('/follow-ups', [App\Http\Controllers\PastoralCareController::class, 'followUps'])->name('pastoral-care.follow-ups');
    Route::post('/follow-ups', [App\Http\Controllers\PastoralCareController::class, 'storeFollowUp'])->name('pastoral-care.follow-ups.store');
    Route::post('/follow-ups/{followUp}/complete', [App\Http\Controllers\PastoralCareController::class, 'completeFollowUp'])->name('pastoral-care.follow-ups.complete');
    Route::get('/prayers', [App\Http\Controllers\PastoralCareController::class, 'prayerRequests'])->name('pastoral-care.prayers');
    Route::post('/prayers', [App\Http\Controllers\PastoralCareController::class, 'storePrayerRequest'])->name('pastoral-care.prayers.store');
    Route::get('/members/{member}/history', [App\Http\Controllers\PastoralCareController::class, 'memberHistory'])->name('pastoral-care.member-history');
});

// Report routes
Route::prefix('reports')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\ReportController::class, 'dashboard'])->name('reports.dashboard');
    Route::get('/members', [App\Http\Controllers\ReportController::class, 'memberReports'])->name('reports.members');
    Route::get('/financial', [App\Http\Controllers\ReportController::class, 'financialReports'])->name('reports.financial');
    Route::get('/attendance', [App\Http\Controllers\ReportController::class, 'attendanceReports'])->name('reports.attendance');
    Route::get('/pastoral-care', [App\Http\Controllers\ReportController::class, 'pastoralCareReports'])->name('reports.pastoral-care');
});

    Route::get('/members/import', [App\Http\Controllers\ImportController::class, 'showForm'])->name('members.import');
    Route::post('/members/import', [App\Http\Controllers\ImportController::class, 'import'])->name('members.import.process');
    Route::resource('members', MemberController::class)->middleware(['auth']);

    // Ministry Pledges Routes
    Route::middleware(['auth'])->prefix('ministry-pledges')->name('ministry-pledges.')->group(function () {
        Route::get('/', [App\Http\Controllers\MinistryPledgeController::class, 'index'])->name('index');
        Route::get('/create/{department}', [App\Http\Controllers\MinistryPledgeController::class, 'create'])->name('create');
        Route::post('/create/{department}', [App\Http\Controllers\MinistryPledgeController::class, 'store'])->name('store');
        Route::get('/{ministryPledge}', [App\Http\Controllers\MinistryPledgeController::class, 'show'])->name('show');
        Route::post('/{ministryPledge}/contribute', [App\Http\Controllers\MinistryPledgeController::class, 'contribute'])->name('contribute');
    });

    // System Settings
    Route::middleware(['auth'])->group(function () {
        Route::get('/settings', [App\Http\Controllers\SystemSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [App\Http\Controllers\SystemSettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-sms', [App\Http\Controllers\SystemSettingController::class, 'testSms'])->name('settings.test-sms');
        Route::get('/admin/audit-logs', [App\Http\Controllers\AuditLogController::class, 'index'])->name('admin.audit-logs');
    });

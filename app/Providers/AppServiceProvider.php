<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Contribution::class, \App\Policies\ContributionPolicy::class);
        
        // Share system settings with all views
        view()->composer('*', function ($view) {
            $settings = \App\Models\SystemSetting::all()->pluck('value', 'key');
            
            // Provide default values
            $view->with('churchName', $settings['church_name'] ?? config('app.name', 'Hosanna International Church'));
            $view->with('churchSlogan', $settings['church_slogan'] ?? 'Kutangaza Injili ya Kristo');
            $view->with('churchAddress', $settings['church_address'] ?? 'Tanzania');
            $view->with('churchPhone', $settings['church_phone'] ?? '');
            $view->with('churchEmail', $settings['church_email'] ?? 'info@hosannachurch.org');
            $view->with('currencySymbol', $settings['currency_symbol'] ?? 'TZS');
            $view->with('churchLogo', $settings['church_logo'] ?? 'images/church-logo.png');

            // Global Notifications (only if authenticated)
            if (\Illuminate\Support\Facades\Auth::check()) {
                $user = \Illuminate\Support\Facades\Auth::user();
                
                // 1. Birthdays Today
                // Show only if user hasn't viewed birthdays today
                $birthdayCount = 0;
                $lastViewedBirthdays = $user->last_viewed_birthdays_at;
                
                if (!$lastViewedBirthdays || !$lastViewedBirthdays->isToday()) {
                    $birthdayCount = \App\Models\Member::todaysBirthdays()->count();
                }
                $view->with('birthdayCount', $birthdayCount);

                // 2. Active Announcements (Current Week)
                // Show only if there are NEW announcements since last view
                $announcementQuery = \App\Models\Announcement::current();
                
                if ($user->last_viewed_announcements_at) {
                    $announcementQuery->where('created_at', '>', $user->last_viewed_announcements_at);
                }
                
                $announcementCount = $announcementQuery->count();
                $view->with('announcementCount', $announcementCount);

                // 3. My Roster (Future Assignments)
                // Show only if there are NEW assignments since last view
                $myRosterCount = 0;
                if ($user->member) {
                    $rosterQuery = \App\Models\Roster::where('member_id', $user->member->id)
                        ->whereHas('event', function($q) {
                            $q->where('date', '>=', now());
                        });
                        
                    if ($user->last_viewed_roster_at) {
                        $rosterQuery->where('created_at', '>', $user->last_viewed_roster_at);
                    }
                    
                    $myRosterCount = $rosterQuery->count();
                }
                $view->with('myRosterCount', $myRosterCount);
                
                // 4. New Projects
                // Show only if there are NEW projects since last view, and clear when viewing pledges/projects
                if (request()->routeIs('pledges.*') || request()->routeIs('projects.*') || request()->routeIs('ministry-pledges.*')) {
                    if (!$user->last_viewed_projects_at || $user->last_viewed_projects_at->lt(now()->subMinute())) {
                        $user->update(['last_viewed_projects_at' => now()]);
                    }
                    $newProjectCount = 0;
                } else {
                    $projectQuery = \App\Models\Project::query();
                    
                    if ($user->last_viewed_projects_at) {
                        $projectQuery->where('created_at', '>', $user->last_viewed_projects_at);
                    }
                    
                    $newProjectCount = $projectQuery->count();
                }
                $view->with('newProjectCount', $newProjectCount);

                // 5. Ministry Notifications (Pledges & Announcements)
                $ministryNotificationCount = $user->unreadNotifications()
                    ->whereIn('type', [
                        'App\Notifications\NewMinistryPledgeNotification',
                        'App\Notifications\NewMinistryAnnouncementNotification'
                    ])
                    ->count();
                $view->with('ministryNotificationCount', $ministryNotificationCount);

                // 6. Inbox Unread Count (Excluding Birthdays & Anniversaries)
                $inboxUnreadCount = $user->unreadNotifications()
                    ->whereNotIn('type', [
                        'App\Notifications\BirthdayGreetingNotification',
                        'App\Notifications\AnniversaryGreetingNotification',
                    ])
                    ->count();
                $view->with('inboxUnreadCount', $inboxUnreadCount);
            }
        });
    }
}

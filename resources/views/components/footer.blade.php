<footer class="gt-footer">
    <div class="gt-footer__noise"></div>

    <div class="gt-footer__inner">
        <div class="gt-footer__top">

            <div class="gt-footer__brand">
                <a href="{{ route('home') }}" class="gt-footer__logoLink" aria-label="Go to home">
                    <div class="gt-footer__logoWrap">
                        <img class="gt-footer__brand-logo" src="{{ asset('images/branding/progresslab-logo.png') }}?v=2" alt="" width="34" height="34">
                        <span class="pl-nav__brand-text">ProgressLab</span>
                    </div>
                </a>
            </div>

            <div class="gt-footer__cols">
                <div class="gt-footer__col">
                    <h3 class="gt-footer__title">Navigation</h3>
                    <ul class="gt-footer__list">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('add-today') }}">Add Today</a></li>
                        <li><a href="{{ route('workouts.index') }}">Workouts</a></li>
                        <li><a href="{{ route('charts.index') }}">Charts</a></li>
                        <li><a href="{{ route('streaks.index') }}">Streaks</a></li>
                        <li><a href="{{ route('achievements.index') }}">Achievements</a></li>
                        <li><a href="{{ route('friends.index') }}">Friends</a></li>
                        <li><a href="{{ route('profile.show') }}">Profile</a></li>
                    </ul>
                </div>
                    
                @php($footerUser = auth()->user())
                <div class="gt-footer__col gt-footer__col--plan">
                    <div class="gt-footer__plan">
                        @if($footerUser?->isTrainer())
                            <span class="gt-footer__plan-kicker">Trainer active</span>
                            <h3>Manage clients with clarity.</h3>
                            <p>Your Trainer plan includes full analytics and the consent-based Clients workspace.</p>
                            <a href="{{ route('plans.index') }}">View your Trainer plan <span aria-hidden="true">→</span></a>
                        @elseif($footerUser?->isPaid())
                            <span class="gt-footer__plan-kicker">ProgressLab+ active</span>
                            <h3>Your full history is unlocked.</h3>
                            <p>Year and all-time analytics are available across your nutrition and strength charts.</p>
                            <a href="{{ route('plans.index') }}">View your plan <span aria-hidden="true">→</span></a>
                        @elseif($footerUser?->isAdmin())
                            <span class="gt-footer__plan-kicker">Membership</span>
                            <h3>See the ProgressLab plans.</h3>
                            <p>Review Free, ProgressLab+, and Trainer access from the member-facing plans page.</p>
                            <a href="{{ route('plans.index') }}">View plans <span aria-hidden="true">→</span></a>
                        @else
                            <span class="gt-footer__plan-kicker">Free plan</span>
                            <h3>Ready to see the full picture?</h3>
                            <p>Unlock year and all-time analytics, complete insights, and long-term comparisons.</p>
                            <a href="{{ route('plans.index') }}">Upgrade to ProgressLab+ <span aria-hidden="true">→</span></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="gt-footer__bottom">
            <p class="gt-footer__copy">
                © {{ now()->year }} ProgressLab. All rights reserved.
            </p>

            <p class="gt-footer__credit">
                Design &amp; development by
                <a
                    href="https://stefanmilovanovic.webflow.io"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="gt-footer__creditLink"
                >
                    Stefan
                </a>
            </p>
        </div>

        <div class="gt-footer__watermark" aria-hidden="true">
            Stefan
        </div>
    </div>
</footer>

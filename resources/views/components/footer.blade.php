<footer class="gt-footer">
    <div class="gt-footer__noise"></div>

    <div class="gt-footer__inner">
        <div class="gt-footer__top">

            <div class="gt-footer__brand">
                <a href="{{ route('home') }}" class="gt-footer__logoLink" aria-label="Go to home">
                    <div class="gt-footer__logoWrap">
                        <span class="pl-nav__brand-dot"></span>
                            </span>
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
                    
                <div class="gt-footer__col">
                    
                </div>

                <div class="gt-footer__col">
                    
                </div>
            </div>
        </div>

        <div class="gt-footer__bottom">
            <p class="gt-footer__copy">
                © {{ now()->year }} GymTracker. All rights reserved.
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
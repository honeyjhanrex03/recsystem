// Premium JS Orchestrator for RECRAS
document.addEventListener('DOMContentLoaded', () => {
    console.log('RECRAS Premium UI Layer: Active');

    // Enhanced Sidebar Orchestration
    const sidebarToggle = document.getElementById('sidebarToggle');
    const body = document.body;

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            body.classList.toggle('sb-sidenav-toggled');

            // Persist the state across session
            localStorage.setItem('recras_sidebar_state', body.classList.contains('sb-sidenav-toggled'));
        });
    }

    // Intelligent Sidebar Memory
    const savedState = localStorage.getItem('recras_sidebar_state');
    if (savedState === 'true' && window.innerWidth > 991) {
        body.classList.add('sb-sidenav-toggled');
    }

    // Auto-close sidebar on small screens after clicking (if needed)
    const sidebarLinks = document.querySelectorAll('#sidebar-wrapper .list-group-item');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 991) {
                body.classList.remove('sb-sidenav-toggled');
            }
        });
    });

    // Premium Interaction: Form Submission Loading
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const btn = this.querySelector('button[type="submit"]');
            if (btn && !this.dataset.isSubmitting) {
                // If the button has a name/value, we must ensure they are sent in POST
                const name = btn.getAttribute('name');
                if (name) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = name;
                    hidden.value = btn.getAttribute('value') || '';
                    this.appendChild(hidden);
                }

                this.dataset.isSubmitting = "true";
                // Instead of disabling immediately, we change appearance to avoid breaking form submission
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.7';
                btn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i> Processing...`;
            }
        });
    });

    // Intelligent Navbar Dynamics
    const nav = document.querySelector('.navbar');
    const isLanding = document.body.classList.contains('landing-page');
    
    if (nav) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                nav.classList.add('shadow-lg');
                if (isLanding) {
                    nav.style.background = "rgba(26, 43, 75, 0.95)"; // Keep dark on landing page scroll
                    nav.style.backdropFilter = "blur(10px)";
                } else {
                    nav.style.background = "rgba(255, 255, 255, 0.95)"; // White for dashboard
                }
            } else {
                nav.classList.remove('shadow-lg');
                if (isLanding) {
                    nav.style.background = "rgba(0,0,0,0.2)"; // Initial transparent state for landing
                    nav.style.backdropFilter = "blur(5px)";
                } else {
                    nav.style.background = "rgba(255, 255, 255, 0.8)"; // Dashboard initial
                }
            }
        });
    }

    // Premium Logout Confirmation
    window.confirmLogout = function (url) {
        Swal.fire({
            title: 'End Session?',
            text: "You will be securely logged out of the RECRAS portal.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1a2b4b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Sign Out',
            cancelButtonText: 'Stay Logged In',
            background: '#ffffff',
            customClass: {
                confirmButton: 'rounded-pill px-4',
                cancelButton: 'rounded-pill px-4'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    };
});

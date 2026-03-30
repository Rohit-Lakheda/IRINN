<script>
document.addEventListener('DOMContentLoaded', function() {
    const allSectionIds = [
        'section-application-info',
        'section-registration',
        'section-representative',
        'section-plan-change',
        'section-application-data',
        'section-irinn-normalized',
        'section-documents',
        'section-history',
        'section-gst-history'
    ];

    function showOnlySection(targetSectionId) {
        console.log('showOnlySection called with:', targetSectionId);
        allSectionIds.forEach(sectionId => {
            const section = document.getElementById(sectionId);
            if (section) {
                if (sectionId === targetSectionId) {
                    console.log('Showing section:', sectionId);
                    section.style.display = 'block';
                    const contentIdMap = {
                        'section-application-info': 'application-info-content',
                        'section-registration': 'registration-content',
                        'section-representative': 'representative-content',
                        'section-plan-change': 'plan-change-content',
                        'section-application-data': 'application-data-content',
                        'section-irinn-normalized': 'irinn-normalized-content',
                        'section-documents': 'documents-content',
                        'section-history': 'history-content',
                        'section-gst-history': 'gst-history-content'
                    };
                    
                    const contentId = contentIdMap[sectionId] || (sectionId + '-content');
                    const content = document.getElementById(contentId);
                    if (content) {
                        content.style.display = 'block';
                        const toggleBtn = section.querySelector('.toggle-section');
                        if (toggleBtn) {
                            const icon = toggleBtn.querySelector('i');
                            if (icon) {
                                icon.classList.remove('bi-chevron-up');
                                icon.classList.add('bi-chevron-down');
                            }
                        }
                    }
                    setTimeout(() => {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                } else {
                    section.style.display = 'none';
                }
            } else {
                console.log('Section not found:', sectionId);
            }
        });

        if (targetSectionId !== 'section-irinn-normalized') {
            document.querySelectorAll('.irinn-step-nav').forEach((n) => n.classList.remove('active'));
        }

        document.querySelectorAll('.toggle-nav-link').forEach(nav => {
            nav.classList.remove('active');
            if (nav.getAttribute('data-target') === targetSectionId) {
                nav.classList.add('active');
            }
        });
    }
    
    window.showOnlySection = showOnlySection;
    
    document.querySelectorAll('.toggle-nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            console.log('Navigation link clicked:', targetId);
            if (targetId) {
                const section = document.getElementById(targetId);
                console.log('Section element found:', section);
                if (section) {
                    showOnlySection(targetId);
                } else {
                    console.error('Section not found in DOM:', targetId);
                    alert('Section "' + targetId + '" not found. Please refresh the page.');
                }
            }
        });
    });

    document.querySelectorAll('.irinn-step-nav').forEach((link) => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const step = this.getAttribute('data-irinn-step');
            if (!step) {
                return;
            }
            showOnlySection('section-irinn-normalized');
            document.querySelectorAll('.irinn-step-panel').forEach((panel) => {
                panel.classList.toggle('d-none', panel.getAttribute('data-irinn-step') !== step);
            });
            document.querySelectorAll('.irinn-step-nav').forEach((nav) => {
                nav.classList.toggle('active', nav.getAttribute('data-irinn-step') === step);
            });
        });
    });
    
    console.log('Available sections on page load:');
    allSectionIds.forEach(id => {
        const el = document.getElementById(id);
        console.log('  -', id, ':', el ? 'EXISTS (display: ' + el.style.display + ')' : 'NOT FOUND');
    });

    document.querySelectorAll('.toggle-section').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const target = targetId ? document.getElementById(targetId) : null;
            const icon = this.querySelector('i');
            if (! target) {
                return;
            }
            if (target.style.display === 'none') {
                target.style.display = 'block';
                if (icon) {
                    icon.classList.remove('bi-chevron-up');
                    icon.classList.add('bi-chevron-down');
                }
            } else {
                target.style.display = 'none';
                if (icon) {
                    icon.classList.remove('bi-chevron-down');
                    icon.classList.add('bi-chevron-up');
                }
            }
        });
    });

    // IRINN draft: Pay with PayU - submit via AJAX then redirect to PayU form (avoid showing JSON)
    const irinnPayForm = document.getElementById('irinn-pay-form');
    if (irinnPayForm) {
        irinnPayForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('irinn-pay-payu-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Redirecting...';
            }
            const formData = new FormData(this);
            // NOTE: form has an input named "action", which shadows HTMLFormElement.action in JS.
            // Always read the real URL from the attribute.
            fetch(this.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(r => r.json())
            .then(function(data) {
                if (data.success && data.payment_url && data.payment_data) {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = data.payment_url;
                    Object.keys(data.payment_data).forEach(function(k) {
                        const i = document.createElement('input');
                        i.type = 'hidden';
                        i.name = k;
                        i.value = data.payment_data[k];
                        f.appendChild(i);
                    });
                    document.body.appendChild(f);
                    f.submit();
                } else {
                    alert(data.message || 'Unable to start payment.');
                    if (btn) { btn.disabled = false; btn.textContent = 'Pay with PayU'; }
                }
            })
            .catch(function(err) {
                console.error(err);
                alert('Payment request failed. Please try again.');
                if (btn) { btn.disabled = false; btn.textContent = 'Pay with PayU'; }
            });
        });
    }

});
</script>

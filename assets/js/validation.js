/**
 * Eventora - Client-Side Validation & Interactivity Engine
 * 
 * Implements:
 * - FR-VAL-001: Client-side validation and clear field-level feedback for all forms
 * - NFR-USA-002: Consistent feedback messaging, states and visual cues
 * - FR-BKG-001 & FR-BKG-002: Seat quantity validation & real-time pricing preview
 * - BR-04: Student seat limits (max 10)
 * - BR-05: Bulk discount rules (10% off for 5+ seats)
 * - BR-11 & BR-12: Non-stacking promo code validation
 * - FR-EVT-006 & FR-EVT-009: Organizer event validation (dates, capacity, limits)
 * - FR-TKT-003: Check-in token validation
 */

(function () {
    'use strict';

    // =========================================================================
    // 1. UTILITY & FEEDBACK HELPERS
    // =========================================================================

    /**
     * Mark an input as valid with optional positive feedback
     */
    function setValid(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        var feedback = getFeedbackElement(input);
        if (feedback) {
            feedback.textContent = '';
            feedback.style.display = 'none';
        }
    }

    /**
     * Mark an input as invalid with an error message
     */
    function setInvalid(input, message) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        var feedback = getFeedbackElement(input);
        if (feedback) {
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
    }

    /**
     * Clear validation state from an input
     */
    function clearValidation(input) {
        input.classList.remove('is-valid', 'is-invalid');
        var feedback = getFeedbackElement(input);
        if (feedback) {
            feedback.textContent = '';
            feedback.style.display = 'none';
        }
    }

    /**
     * Locate or create the .invalid-feedback sibling for an input
     */
    function getFeedbackElement(input) {
        var container = input.closest('.input-group') || input.parentElement;
        var feedback = container.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            container.appendChild(feedback);
        }
        return feedback;
    }

    /**
     * Standard RFC 5322 compliant email regex check
     */
    function isValidEmail(email) {
        var re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(String(email).trim());
    }

    /**
     * Phone number validator (Sri Lankan local / international format)
     */
    function isValidPhone(phone) {
        var re = /^\+?[0-9\s\-()]{9,15}$/;
        return re.test(String(phone).trim());
    }

    /**
     * Password strength evaluation (score 0-4)
     */
    function evaluatePasswordStrength(password) {
        var score = 0;
        if (!password) return 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        return Math.min(score, 4);
    }

    // =========================================================================
    // 2. STUDENT REGISTRATION FORM VALIDATION
    // =========================================================================

    function initStudentRegistration() {
        var form = document.getElementById('studentRegisterForm');
        if (!form) return;

        var nameInput = form.querySelector('#name');
        var emailInput = form.querySelector('#email');
        var passInput = form.querySelector('#password');
        var confirmInput = form.querySelector('#confirm_password');
        var termsInput = form.querySelector('#terms');

        // Create password strength indicator meter if not present
        var strengthContainer = document.getElementById('passStrengthContainer');
        if (!strengthContainer && passInput) {
            strengthContainer = document.createElement('div');
            strengthContainer.id = 'passStrengthContainer';
            strengthContainer.className = 'mt-1 mb-2';
            strengthContainer.innerHTML = '<div class="progress" style="height: 4px;"><div id="passStrengthBar" class="progress-bar" style="width: 0%"></div></div><small id="passStrengthText" class="text-muted" style="font-size: 0.75rem;"></small>';
            passInput.parentElement.appendChild(strengthContainer);
        }

        // Real-time name validation
        if (nameInput) {
            nameInput.addEventListener('input', function () {
                var val = this.value.trim();
                if (val.length < 3) {
                    setInvalid(this, 'Full name must be at least 3 characters long.');
                } else if (!/^[a-zA-Z\s\.\'-]+$/.test(val)) {
                    setInvalid(this, 'Name can only contain letters, spaces and periods.');
                } else {
                    setValid(this);
                }
            });
        }

        // Real-time email validation
        if (emailInput) {
            emailInput.addEventListener('input', function () {
                var val = this.value.trim();
                if (!val) {
                    setInvalid(this, 'Email address is required.');
                } else if (!isValidEmail(val)) {
                    setInvalid(this, 'Please enter a valid email address (e.g. name@domain.com).');
                } else {
                    setValid(this);
                }
            });
        }

        // Real-time password validation & strength meter
        if (passInput) {
            passInput.addEventListener('input', function () {
                var val = this.value;
                var strength = evaluatePasswordStrength(val);
                var bar = document.getElementById('passStrengthBar');
                var text = document.getElementById('passStrengthText');

                if (bar && text) {
                    if (val.length === 0) {
                        bar.style.width = '0%';
                        text.textContent = '';
                    } else if (strength <= 1) {
                        bar.style.width = '25%';
                        bar.className = 'progress-bar bg-danger';
                        text.textContent = 'Weak: Add numbers and uppercase letters';
                        text.className = 'text-danger small';
                    } else if (strength === 2) {
                        bar.style.width = '50%';
                        bar.className = 'progress-bar bg-warning';
                        text.textContent = 'Moderate: Add special characters';
                        text.className = 'text-warning small';
                    } else if (strength === 3) {
                        bar.style.width = '75%';
                        bar.className = 'progress-bar bg-info';
                        text.textContent = 'Good password';
                        text.className = 'text-info small';
                    } else {
                        bar.style.width = '100%';
                        bar.className = 'progress-bar bg-success';
                        text.textContent = 'Strong password';
                        text.className = 'text-success small';
                    }
                }

                if (val.length < 8) {
                    setInvalid(this, 'Password must be at least 8 characters long.');
                } else if (!/[A-Za-z]/.test(val) || !/[0-9]/.test(val)) {
                    setInvalid(this, 'Password must include both letters and numbers.');
                } else {
                    setValid(this);
                }

                // Re-evaluate confirm password if already typed
                if (confirmInput && confirmInput.value) {
                    if (confirmInput.value !== val) {
                        setInvalid(confirmInput, 'Passwords do not match.');
                    } else {
                        setValid(confirmInput);
                    }
                }
            });
        }

        // Confirm password matching
        if (confirmInput) {
            confirmInput.addEventListener('input', function () {
                if (this.value !== (passInput ? passInput.value : '')) {
                    setInvalid(this, 'Passwords do not match.');
                } else {
                    setValid(this);
                }
            });
        }

        // Form submission interceptor
        form.addEventListener('submit', function (e) {
            var isValid = true;

            if (nameInput && nameInput.value.trim().length < 3) {
                setInvalid(nameInput, 'Full official name is required (min 3 chars).');
                isValid = false;
            }
            if (emailInput && !isValidEmail(emailInput.value)) {
                setInvalid(emailInput, 'Valid university email is required.');
                isValid = false;
            }
            if (passInput && passInput.value.length < 8) {
                setInvalid(passInput, 'Password must be at least 8 characters.');
                isValid = false;
            }
            if (confirmInput && confirmInput.value !== (passInput ? passInput.value : '')) {
                setInvalid(confirmInput, 'Passwords do not match.');
                isValid = false;
            }
            if (termsInput && !termsInput.checked) {
                setInvalid(termsInput, 'You must agree to campus guidelines.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                // Focus first invalid element
                var firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
            }
        });
    }

    // =========================================================================
    // 3. ORGANIZER REGISTRATION FORM VALIDATION
    // =========================================================================

    function initOrganizerRegistration() {
        var form = document.getElementById('organizerRegisterForm');
        if (!form) return;

        var nameInput = form.querySelector('#org_name');
        var orgInput = form.querySelector('#organization');
        var emailInput = form.querySelector('#email');
        var phoneInput = form.querySelector('#phone');
        var bioInput = form.querySelector('#bio');
        var passInput = form.querySelector('#password');
        var confirmInput = form.querySelector('#confirm_password');

        if (nameInput) {
            nameInput.addEventListener('input', function () {
                if (this.value.trim().length < 3) {
                    setInvalid(this, 'Representative name must be at least 3 characters.');
                } else {
                    setValid(this);
                }
            });
        }

        if (orgInput) {
            orgInput.addEventListener('input', function () {
                if (this.value.trim().length < 3) {
                    setInvalid(this, 'Society or Club name is required (min 3 characters).');
                } else {
                    setValid(this);
                }
            });
        }

        if (phoneInput) {
            phoneInput.addEventListener('input', function () {
                if (!isValidPhone(this.value)) {
                    setInvalid(this, 'Enter a valid phone number (e.g. +94 77 123 4567).');
                } else {
                    setValid(this);
                }
            });
        }

        if (bioInput) {
            bioInput.addEventListener('input', function () {
                if (this.value.trim().length < 15) {
                    setInvalid(this, 'Please provide a brief description (at least 15 characters).');
                } else {
                    setValid(this);
                }
            });
        }

        form.addEventListener('submit', function (e) {
            var isValid = true;
            if (nameInput && nameInput.value.trim().length < 3) {
                setInvalid(nameInput, 'Representative contact name is required.');
                isValid = false;
            }
            if (orgInput && orgInput.value.trim().length < 3) {
                setInvalid(orgInput, 'Club / Society name is required.');
                isValid = false;
            }
            if (emailInput && !isValidEmail(emailInput.value)) {
                setInvalid(emailInput, 'Valid official email is required.');
                isValid = false;
            }
            if (phoneInput && !isValidPhone(phoneInput.value)) {
                setInvalid(phoneInput, 'Valid phone number is required.');
                isValid = false;
            }
            if (bioInput && bioInput.value.trim().length < 15) {
                setInvalid(bioInput, 'Please provide at least 15 characters describing your club.');
                isValid = false;
            }
            if (passInput && passInput.value.length < 8) {
                setInvalid(passInput, 'Password must be at least 8 characters.');
                isValid = false;
            }
            if (confirmInput && confirmInput.value !== (passInput ? passInput.value : '')) {
                setInvalid(confirmInput, 'Passwords do not match.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                var firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
            }
        });
    }

    // =========================================================================
    // 4. LOGIN FORM VALIDATION
    // =========================================================================

    function initLoginForm() {
        var form = document.getElementById('loginForm');
        if (!form) return;

        var emailInput = form.querySelector('#email');
        var passInput = form.querySelector('#password');

        if (emailInput) {
            emailInput.addEventListener('input', function () {
                if (!isValidEmail(this.value)) {
                    setInvalid(this, 'Please enter a valid email address.');
                } else {
                    setValid(this);
                }
            });
        }

        if (passInput) {
            passInput.addEventListener('input', function () {
                if (!this.value) {
                    setInvalid(this, 'Password cannot be empty.');
                } else {
                    setValid(this);
                }
            });
        }

        form.addEventListener('submit', function (e) {
            var isValid = true;
            if (emailInput && !isValidEmail(emailInput.value)) {
                setInvalid(emailInput, 'Valid email is required.');
                isValid = false;
            }
            if (passInput && !passInput.value) {
                setInvalid(passInput, 'Password is required.');
                isValid = false;
            }
            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }

    // =========================================================================
    // 5. BOOKING FORM & DYNAMIC PRICING ENGINE
    // =========================================================================

    function initBookingEngine() {
        var form = document.getElementById('bookingForm');
        if (!form) return;

        var seatInput = form.querySelector('#seat-quantity');
        var promoInput = form.querySelector('#promo-input');
        var promoBtn = form.querySelector('button[type="button"]');
        var subtotalEl = document.getElementById('subtotal-val');
        var discountEl = document.getElementById('discount-val');
        var totalEl = document.getElementById('total-val');

        // Extract unit price (default to 500.00 if not specified on element)
        var unitPrice = 500.00;
        if (form.dataset.unitPrice) {
            unitPrice = parseFloat(form.dataset.unitPrice) || 0.00;
        }

        var maxPerStudent = 5;
        if (seatInput && seatInput.getAttribute('max')) {
            maxPerStudent = parseInt(seatInput.getAttribute('max'), 10) || 10;
        }

        // Active promo state
        var activePromoCode = '';
        var activePromoDiscount = 0.00;

        // Known promo codes for client-side preview simulation (BR-11)
        var promoDirectory = {
            'WELCOME10': { type: 'percentage', val: 0.10, minSpend: 500.00, cap: 200.00 },
            'FLAT100': { type: 'fixed', val: 100.00, minSpend: 300.00, cap: 100.00 }
        };

        function recalculate() {
            var qty = parseInt(seatInput.value, 10);
            if (isNaN(qty) || qty < 1) {
                qty = 1;
            }

            // Subtotal (BR-09: 2 decimal places, LKR)
            var subtotal = unitPrice * qty;

            // Bulk discount calculation: BR-05 (10% off for 5+ seats)
            var bulkDiscount = 0.00;
            if (qty >= 5) {
                bulkDiscount = subtotal * 0.10;
            }

            // Promo discount calculation: BR-11
            var promoDiscount = 0.00;
            if (activePromoCode && promoDirectory[activePromoCode]) {
                var p = promoDirectory[activePromoCode];
                if (subtotal >= p.minSpend) {
                    if (p.type === 'percentage') {
                        promoDiscount = subtotal * p.val;
                        if (p.cap && promoDiscount > p.cap) {
                            promoDiscount = p.cap;
                        }
                    } else if (p.type === 'fixed') {
                        promoDiscount = p.val;
                    }
                }
            }

            // Non-stacking rule: BR-12 (Apply the single best eligible discount)
            var appliedDiscount = Math.max(bulkDiscount, promoDiscount);
            appliedDiscount = Math.min(appliedDiscount, subtotal); // Cannot exceed subtotal
            appliedDiscount = Math.round(appliedDiscount * 100) / 100;

            var total = Math.max(0, subtotal - appliedDiscount);
            total = Math.round(total * 100) / 100;

            // Update UI elements
            if (subtotalEl) subtotalEl.textContent = subtotal.toFixed(2);
            if (discountEl) {
                discountEl.textContent = appliedDiscount.toFixed(2);
                if (appliedDiscount > 0) {
                    var discountTag = (bulkDiscount >= promoDiscount && qty >= 5) ? ' (Bulk 10%)' : ' (Promo applied)';
                    discountEl.title = discountTag;
                }
            }
            if (totalEl) totalEl.textContent = total.toFixed(2);
        }

        if (seatInput) {
            seatInput.addEventListener('input', function () {
                var val = parseInt(this.value, 10);
                if (isNaN(val) || val < 1) {
                    setInvalid(this, 'You must select at least 1 seat.');
                } else if (val > maxPerStudent) {
                    setInvalid(this, 'Maximum seat limit is ' + maxPerStudent + ' seats per student.');
                } else {
                    setValid(this);
                }
                recalculate();
            });

            seatInput.addEventListener('change', recalculate);
        }

        // Promo code application handler
        if (promoBtn && promoInput) {
            promoBtn.addEventListener('click', function () {
                var code = promoInput.value.trim().toUpperCase();
                promoInput.value = code;

                if (!code) {
                    activePromoCode = '';
                    clearValidation(promoInput);
                    recalculate();
                    return;
                }

                if (promoDirectory[code]) {
                    var promo = promoDirectory[code];
                    var currentSubtotal = unitPrice * (parseInt(seatInput.value, 10) || 1);
                    if (currentSubtotal < promo.minSpend) {
                        setInvalid(promoInput, 'Minimum spend of LKR ' + promo.minSpend.toFixed(2) + ' required for code ' + code);
                        activePromoCode = '';
                    } else {
                        activePromoCode = code;
                        setValid(promoInput);
                    }
                } else {
                    activePromoCode = '';
                    setInvalid(promoInput, 'Invalid or expired promo code: ' + code);
                }
                recalculate();
            });
        }

        // Form submission checks
        form.addEventListener('submit', function (e) {
            var qty = parseInt(seatInput.value, 10);
            if (isNaN(qty) || qty < 1 || qty > maxPerStudent) {
                e.preventDefault();
                setInvalid(seatInput, 'Please choose a valid number of seats (1 - ' + maxPerStudent + ').');
                seatInput.focus();
            }
        });

        // Initial calculation on page load
        recalculate();
    }

    // =========================================================================
    // 6. ORGANIZER EVENT CREATION & EDITING FORM VALIDATION
    // =========================================================================

    function initEventFormValidation() {
        var form = document.getElementById('eventForm');
        if (!form) return;

        var titleInput = form.querySelector('#event_title');
        var catSelect = form.querySelector('#category');
        var venueInput = form.querySelector('#venue');
        var startInput = form.querySelector('#start_date');
        var endInput = form.querySelector('#end_date');
        var priceInput = form.querySelector('#price');
        var capInput = form.querySelector('#capacity');
        var maxSeatInput = form.querySelector('#max_per_student');
        var descInput = form.querySelector('#description');

        if (titleInput) {
            titleInput.addEventListener('input', function () {
                if (this.value.trim().length < 5) {
                    setInvalid(this, 'Title must be at least 5 characters long.');
                } else {
                    setValid(this);
                }
            });
        }

        if (catSelect) {
            catSelect.addEventListener('change', function () {
                if (!this.value) {
                    setInvalid(this, 'Please select an event category.');
                } else {
                    setValid(this);
                }
            });
        }

        if (venueInput) {
            venueInput.addEventListener('input', function () {
                if (this.value.trim().length < 3) {
                    setInvalid(this, 'Venue location is required.');
                } else {
                    setValid(this);
                }
            });
        }

        // Chronological datetime validation
        function validateDates() {
            if (!startInput || !endInput) return true;
            var startVal = startInput.value;
            var endVal = endInput.value;

            if (!startVal) {
                setInvalid(startInput, 'Start date & time is required.');
                return false;
            } else {
                setValid(startInput);
            }

            if (endVal) {
                var sDate = new Date(startVal);
                var eDate = new Date(endVal);
                if (eDate <= sDate) {
                    setInvalid(endInput, 'End date & time must be chronologically after the start time.');
                    return false;
                } else {
                    setValid(endInput);
                }
            }
            return true;
        }

        if (startInput) startInput.addEventListener('change', validateDates);
        if (endInput) endInput.addEventListener('change', validateDates);

        if (priceInput) {
            priceInput.addEventListener('input', function () {
                var val = parseFloat(this.value);
                if (isNaN(val) || val < 0) {
                    setInvalid(this, 'Price cannot be negative (enter 0 for free events).');
                } else {
                    setValid(this);
                }
            });
        }

        if (capInput) {
            capInput.addEventListener('input', function () {
                var val = parseInt(this.value, 10);
                if (isNaN(val) || val < 1) {
                    setInvalid(this, 'Event capacity must be at least 1 seat.');
                } else {
                    setValid(this);
                }
            });
        }

        if (maxSeatInput) {
            maxSeatInput.addEventListener('input', function () {
                var val = parseInt(this.value, 10);
                if (isNaN(val) || val < 1 || val > 10) {
                    setInvalid(this, 'Max seats per student must be between 1 and 10.');
                } else {
                    setValid(this);
                }
            });
        }

        if (descInput) {
            descInput.addEventListener('input', function () {
                if (this.value.trim().length < 20) {
                    setInvalid(this, 'Please provide an agenda or description of at least 20 characters.');
                } else {
                    setValid(this);
                }
            });
        }

        form.addEventListener('submit', function (e) {
            var isValid = true;
            if (titleInput && titleInput.value.trim().length < 5) {
                setInvalid(titleInput, 'Event title is required (min 5 chars).');
                isValid = false;
            }
            if (catSelect && !catSelect.value) {
                setInvalid(catSelect, 'Event category is required.');
                isValid = false;
            }
            if (venueInput && venueInput.value.trim().length < 3) {
                setInvalid(venueInput, 'Campus venue is required.');
                isValid = false;
            }
            if (!validateDates()) {
                isValid = false;
            }
            if (priceInput && (parseFloat(priceInput.value) < 0 || isNaN(parseFloat(priceInput.value)))) {
                setInvalid(priceInput, 'Valid price is required (>= 0.00).');
                isValid = false;
            }
            if (capInput && (parseInt(capInput.value, 10) < 1 || isNaN(parseInt(capInput.value, 10)))) {
                setInvalid(capInput, 'Capacity must be at least 1 seat.');
                isValid = false;
            }
            if (descInput && descInput.value.trim().length < 20) {
                setInvalid(descInput, 'Description is required (min 20 characters).');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                var firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
            }
        });
    }

    // =========================================================================
    // 7. ADMIN PROMO CODE CREATION MODAL VALIDATION
    // =========================================================================

    function initPromoFormValidation() {
        var form = document.getElementById('createPromoForm');
        if (!form) return;

        var codeInput = form.querySelector('#promo_code');
        var typeSelect = form.querySelector('#discount_type');
        var valInput = form.querySelector('#discount_value');

        if (codeInput) {
            codeInput.addEventListener('input', function () {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
                if (this.value.length < 3) {
                    setInvalid(this, 'Code must be at least 3 alphanumeric characters.');
                } else {
                    setValid(this);
                }
            });
        }

        if (valInput) {
            valInput.addEventListener('input', function () {
                var num = parseFloat(this.value);
                var type = typeSelect ? typeSelect.value : 'percentage';
                if (isNaN(num) || num <= 0) {
                    setInvalid(this, 'Discount value must be greater than 0.');
                } else if (type === 'percentage' && num > 100) {
                    setInvalid(this, 'Percentage discount cannot exceed 100%.');
                } else {
                    setValid(this);
                }
            });
        }
    }

    // =========================================================================
    // 8. VENUE CHECK-IN TOKEN SUBMISSION VALIDATION
    // =========================================================================

    function initCheckinValidation() {
        var form = document.getElementById('tokenCheckinForm');
        if (!form) return;

        var tokenInput = form.querySelector('#ticket_token');
        if (tokenInput) {
            tokenInput.addEventListener('input', function () {
                var val = this.value.trim();
                if (val.length < 6) {
                    setInvalid(this, 'Token code must be at least 6 characters.');
                } else {
                    setValid(this);
                }
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!tokenInput || tokenInput.value.trim().length < 6) {
                if (tokenInput) setInvalid(tokenInput, 'Please enter a valid ticket token.');
                return;
            }

            // Client-side simulation of check-in response
            var enteredToken = tokenInput.value.trim();
            alert('Validating ticket token: ' + enteredToken + '\nStatus: Entry Verified (1 Seat Confirmed)');
            setValid(tokenInput);
            tokenInput.value = '';
        });
    }

    // =========================================================================
    // 9. BOOTSTRAP AUTO-INITIALIZER
    // =========================================================================

    document.addEventListener('DOMContentLoaded', function () {
        initStudentRegistration();
        initOrganizerRegistration();
        initLoginForm();
        initBookingEngine();
        initEventFormValidation();
        initPromoFormValidation();
        initCheckinValidation();
    });

})();

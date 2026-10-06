const API_URL = '../php';
let currentUser = null;

const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const contactForm = document.getElementById('contact-form');
const loginModal = document.getElementById('login-modal');
const registerModal = document.getElementById('register-modal');
const loginLink = document.querySelector('[href="#login"]');
const registerLink = document.querySelector('[href="#register"]');
const checkoutForm = document.querySelector('.payment-form');

function openModal(modal) {
    if (modal) modal.style.display = 'block';
}

function closeModal(modal) {
    if (modal) modal.style.display = 'none';
}

function persistCustomer(customer) {
    localStorage.setItem('sessionId', customer.token);
    localStorage.setItem('lostnationCustomer', JSON.stringify(customer));
    currentUser = customer;
}

document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.addEventListener('click', function() {
        const modal = this.closest('.modal');
        if (modal) modal.style.display = 'none';
    });
});

if (loginLink) {
    loginLink.addEventListener('click', (event) => {
        event.preventDefault();
        openModal(loginModal);
    });
}

if (registerLink) {
    registerLink.addEventListener('click', (event) => {
        event.preventDefault();
        openModal(registerModal);
    });
}

window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const email = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;

        try {
            const response = await fetch(`${API_URL}/auth/login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (data.success) {
                persistCustomer(data.data.customer);
                showAlert('Login successful. Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 1000);
            } else {
                showAlert(data.message || 'Login failed', 'error');
            }
        } catch (error) {
            showAlert('Error during login: ' + error.message, 'error');
        }
    });
}

if (registerForm) {
    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const firstName = document.getElementById('first-name').value.trim();
        const lastName = document.getElementById('last-name').value.trim();
        const email = document.getElementById('reg-email').value.trim();
        const phoneNumber = document.getElementById('phone').value.trim();
        const password = document.getElementById('password').value;

        try {
            const response = await fetch(`${API_URL}/auth/register.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ firstName, lastName, email, phoneNumber, password })
            });

            const data = await response.json();

            if (data.success) {
                persistCustomer(data.data.customer);
                showAlert('Registration successful. Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 1000);
            } else {
                showAlert(data.message || 'Registration failed', 'error');
            }
        } catch (error) {
            showAlert('Error during registration: ' + error.message, 'error');
        }
    });
}

if (checkoutForm) {
    checkoutForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const emailInput = checkoutForm.querySelector('input[type="email"]');
        const email = (emailInput?.value || '').trim();
        const amount = 87000;
        const item = 'Lostnation order';

        if (!email) {
            showAlert('Please enter your email before checking out.', 'error');
            return;
        }

        try {
            const response = await fetch(`${API_URL}/payments/payment.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ amount, email, item })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Payment could not be completed.');
            }

            const order = {
                id: data.data.paymentReference,
                item,
                amount: `₦${amount.toLocaleString('en-NG')}`,
                status: 'Paid'
            };

            const orders = JSON.parse(localStorage.getItem('lostnationOrders') || '[]');
            orders.unshift(order);
            localStorage.setItem('lostnationOrders', JSON.stringify(orders));

            const payments = JSON.parse(localStorage.getItem('lostnationPayments') || '[]');
            payments.unshift({
                reference: data.data.paymentReference,
                amount: `₦${amount.toLocaleString('en-NG')}`,
                status: 'Paid'
            });
            localStorage.setItem('lostnationPayments', JSON.stringify(payments));

            showAlert('Order placed successfully.', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1200);
        } catch (error) {
            showAlert(error.message || 'Payment service is unavailable.', 'error');
        }
    });
}

if (contactForm) {
    contactForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const subject = document.getElementById('subject').value;
        const message = document.getElementById('message').value;

        try {
            const response = await fetch(`${API_URL}/contact/send_message.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, subject, message })
            });

            const data = await response.json();
            if (data.success) {
                showAlert(data.message, 'success');
                contactForm.reset();
            } else {
                showAlert(data.message || 'Error sending message', 'error');
            }
        } catch (error) {
            showAlert('Error: ' + error.message, 'error');
        }
    });
}

function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    document.body.prepend(alert);

    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-4px)';
        setTimeout(() => alert.remove(), 220);
    }, 4000);
}

function checkAuth() {
    const sessionId = localStorage.getItem('sessionId');
    if (sessionId) {
        const navLinks = document.querySelector('.nav-links');
        if (navLinks) {
            navLinks.innerHTML = `
                <li><a href="#home">Home</a></li>
                <li><a href="#shop">Shop</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="dashboard.html">Dashboard</a></li>
                <li><a href="#" id="logout-btn">Logout</a></li>
            `;

            document.getElementById('logout-btn').addEventListener('click', (event) => {
                event.preventDefault();
                logoutFromSession();
            });
        }
    }
}

async function logoutFromSession() {
    try {
        await fetch(`${API_URL}/auth/logout.php`, {
            method: 'POST',
            credentials: 'include'
        });
    } catch (error) {
        console.warn('Logout request failed:', error);
    }

    localStorage.removeItem('sessionId');
    localStorage.removeItem('lostnationCustomer');
    window.location.reload();
}

document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
});

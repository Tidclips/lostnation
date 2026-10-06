const API_URL = '../php';
let currentCustomer = null;
let authToken = null;

const profileForm = document.getElementById('profile-form');
const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
const contentSections = document.querySelectorAll('.content-section');
const payBtn = document.getElementById('pay-btn');
const logoutBtn = document.querySelector('.nav-link.logout');

const defaultCustomer = {
    firstName: 'Aisha',
    lastName: 'K.',
    email: 'hello@lostnation.com',
    phone: '+234 800 000 0000',
    address: 'Lekki Phase 1, Lagos'
};

const defaultOrders = [
    { id: 'LN-101', item: 'Drifter graphic tee', amount: '₦42,000', status: 'Paid' },
    { id: 'LN-204', item: 'Coordinates tee', amount: '₦45,000', status: 'Processing' }
];

async function loadCustomerSession() {
    const sessionToken = localStorage.getItem('sessionId');
    if (!sessionToken) {
        window.location.href = 'index.html';
        return;
    }

    const fallbackCustomer = JSON.parse(localStorage.getItem('lostnationCustomer') || '{}');

    const lookupCandidates = [
        `${API_URL}/auth/me.php`,
        `${API_URL}/auth/verify.php`
    ];

    for (const endpoint of lookupCandidates) {
        try {
            const response = await fetch(endpoint, {
                method: 'GET',
                credentials: 'include'
            });

            const contentType = response.headers.get('content-type') || '';
            if (!response.ok || !contentType.includes('application/json')) {
                continue;
            }

            const data = await response.json();
            const payloadCustomer = data.data?.customer || { id: data.data?.customerId };
            if (data.success && (payloadCustomer || data.data?.customerId)) {
                currentCustomer = { ...defaultCustomer, ...fallbackCustomer, ...payloadCustomer };
                if (data.data?.customerId && !currentCustomer.id) {
                    currentCustomer.id = data.data.customerId;
                }
                localStorage.setItem('lostnationCustomer', JSON.stringify(currentCustomer));
                return;
            }
        } catch (error) {
            console.warn(`Session lookup failed for ${endpoint}:`, error);
        }
    }

    currentCustomer = { ...defaultCustomer, ...fallbackCustomer };
}

document.addEventListener('DOMContentLoaded', async () => {
    authToken = localStorage.getItem('sessionId') || null;
    await loadCustomerSession();

    populateProfile();
    renderOrders();
    setupNavigation();
    setupEventListeners();
});

function populateProfile() {
    if (!currentCustomer) {
        return;
    }

    document.getElementById('user-name').textContent = `${currentCustomer.firstName} ${currentCustomer.lastName}`;
    document.getElementById('first-name').value = currentCustomer.firstName;
    document.getElementById('last-name').value = currentCustomer.lastName;
    document.getElementById('email').value = currentCustomer.email;
    document.getElementById('phone').value = currentCustomer.phone || currentCustomer.phoneNumber || '';
    document.getElementById('address').value = currentCustomer.address || '';

    document.getElementById('order-status').textContent = 'In review';
    document.getElementById('payment-status').textContent = 'Pending';
    document.getElementById('saved-items').textContent = String(JSON.parse(localStorage.getItem('lostnationCart') || '[]').length || 3);
    document.getElementById('shipping-status').textContent = 'Awaiting dispatch';
    document.getElementById('current-payment-status').textContent = 'Pending';
}

function renderOrders() {
    const list = document.getElementById('orders-list');
    const savedOrders = JSON.parse(localStorage.getItem('lostnationOrders') || 'null') || defaultOrders;

    list.innerHTML = savedOrders.map(order => `
        <div class="order-card">
            <h4>${order.item}</h4>
            <p><strong>Order:</strong> ${order.id}</p>
            <p><strong>Amount:</strong> ${order.amount}</p>
            <p><strong>Status:</strong> ${order.status}</p>
        </div>
    `).join('');

    const paymentList = document.getElementById('payment-list');
    const payments = JSON.parse(localStorage.getItem('lostnationPayments') || 'null') || [];
    paymentList.innerHTML = payments.length ? payments.map(payment => `
        <p><strong>${payment.reference || payment.id}</strong> — ${payment.amount}</p>
    `).join('') : '<p>No payments yet</p>';
}

function setupNavigation() {
    navLinks.forEach(link => {
        link.addEventListener('click', (event) => {
            if (link.classList.contains('logout')) return;
            event.preventDefault();
            const targetId = link.getAttribute('href').substring(1);
            switchSection(targetId);
        });
    });
}

function switchSection(sectionId) {
    contentSections.forEach(section => section.classList.remove('active'));
    navLinks.forEach(link => link.classList.remove('active'));

    const targetSection = document.getElementById(sectionId);
    const targetLink = document.querySelector(`.nav-link[href="#${sectionId}"]`);

    if (targetSection) {
        targetSection.classList.add('active');
        document.getElementById('page-title').textContent = targetSection.querySelector('h2')?.textContent || 'Dashboard';
    }

    if (targetLink) {
        targetLink.classList.add('active');
    }

    window.scrollTo(0, 0);
}

function setupEventListeners() {
    if (profileForm) {
        profileForm.addEventListener('submit', updateProfile);
    }

    if (payBtn) {
        payBtn.addEventListener('click', initiatePayment);
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', (event) => {
            event.preventDefault();
            logout();
        });
    }
}

async function updateProfile(event) {
    event.preventDefault();

    currentCustomer = {
        ...currentCustomer,
        firstName: document.getElementById('first-name').value,
        lastName: document.getElementById('last-name').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value,
    };

    localStorage.setItem('lostnationCustomer', JSON.stringify(currentCustomer));
    populateProfile();
    showAlert('Profile updated successfully', 'success');
}

async function initiatePayment() {
    const amount = 87000;
    const email = currentCustomer?.email || 'guest@lostnation.com';
    try {
        const response = await fetch(`${API_URL}/payments/payment.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ amount, email, item: 'Lostnation order' })
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Payment could not be completed.');
        }

        const reference = result.data.paymentReference;
        const displayAmount = `₦${amount.toLocaleString('en-NG')}`;
        const order = {
            id: reference,
            item: 'Lostnation order',
            amount: displayAmount,
            status: 'Paid'
        };

        const payments = JSON.parse(localStorage.getItem('lostnationPayments') || '[]');
        payments.unshift({ reference, amount: displayAmount, status: 'Paid' });
        localStorage.setItem('lostnationPayments', JSON.stringify(payments));

        const savedOrders = JSON.parse(localStorage.getItem('lostnationOrders') || 'null') || defaultOrders;
        savedOrders.unshift(order);
        localStorage.setItem('lostnationOrders', JSON.stringify(savedOrders));

        renderOrders();
        document.getElementById('payment-status').textContent = 'Paid';
        document.getElementById('current-payment-status').textContent = 'Paid';
        document.getElementById('order-status').textContent = 'Confirmed';
        showAlert('Payment confirmed and saved.', 'success');
    } catch (error) {
        showAlert(error.message || 'Payment service is unavailable.', 'error');
    }
}

function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 1rem; border-radius: 8px; z-index: 1000;';

    if (type === 'success') {
        alert.style.backgroundColor = '#d1fae5';
        alert.style.color = '#065f46';
    } else if (type === 'error') {
        alert.style.backgroundColor = '#fee2e2';
        alert.style.color = '#7f1d1d';
    }

    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 4000);
}

async function logout() {
    try {
        await fetch(`${API_URL}/auth/logout.php`, { method: 'POST', credentials: 'include' });
    } catch (error) {
        console.warn('Logout endpoint not available:', error);
    }

    localStorage.removeItem('sessionId');
    localStorage.removeItem('lostnationCustomer');
    window.location.href = 'index.html';
}

window.switchSection = switchSection;

const productCards = document.querySelectorAll('.product-card');
const productModal = document.getElementById('product-modal');
const detailImage = document.getElementById('detail-image');
const detailTitle = document.getElementById('detail-title');
const detailDescription = document.getElementById('detail-description');
const detailColor = document.getElementById('detail-color');
const detailPrice = document.getElementById('detail-price');
const detailAdd = document.getElementById('detail-add');
const bagCount = document.querySelector('.bag-count');
let selectedProduct = null;
let cartItems = JSON.parse(localStorage.getItem('lostnationCart') || '[]');
let cartCount = cartItems.length || 2;

function formatPrice(value) {
    return `₦${Number(value).toLocaleString('en-NG')}`;
}

function persistCart() {
    localStorage.setItem('lostnationCart', JSON.stringify(cartItems));
    cartCount = cartItems.length;
    updateBag();
}

function updateBag() {
    if (bagCount) {
        bagCount.textContent = String(cartCount).padStart(2, '0');
    }
}

function showToast(message) {
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.classList.add('fade-out');
        setTimeout(() => existingToast.remove(), 220);
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 220);
    }, 2200);
}

function addToBag(product) {
    const productName = product.dataset.product || 'Lostnation item';
    const price = Number(product.dataset.price || 0);
    cartItems.push({ name: productName, price, addedAt: new Date().toISOString() });
    persistCart();

    product.classList.add('product-bump');
    product.querySelector('.quick-add')?.classList.add('added');
    const quickButton = product.querySelector('.quick-add');
    if (quickButton) {
        quickButton.textContent = 'Added ✓';
        setTimeout(() => {
            quickButton.textContent = 'Quick add +';
            quickButton.classList.remove('added');
            product.classList.remove('product-bump');
        }, 1300);
    }
}

function openDetails(product) {
    if (!productModal || !detailImage || !detailTitle || !detailDescription || !detailColor || !detailPrice || !detailAdd) {
        return;
    }

    selectedProduct = product;
    const image = product.querySelector('.shirt-photo');
    detailImage.src = image.src;
    detailImage.alt = image.alt;
    detailTitle.textContent = product.dataset.product;
    detailDescription.textContent = product.dataset.description;
    detailColor.textContent = `${product.dataset.color} / Limited drop`;
    detailPrice.textContent = formatPrice(product.dataset.price);
    productModal.classList.add('is-open');
    productModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    detailAdd.focus();
}

function closeDetails() {
    if (!productModal) return;
    productModal.classList.remove('is-open');
    productModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
}

productCards.forEach(product => {
    product.addEventListener('click', event => {
        if (event.target.closest('.quick-add')) {
            addToBag(product);
            showToast(`${product.dataset.product} added to bag`);
            return;
        }
        openDetails(product);
    });
});

document.querySelectorAll('[data-close-modal]').forEach(element => {
    element.addEventListener('click', closeDetails);
});

if (detailAdd) {
    detailAdd.addEventListener('click', () => {
        if (!selectedProduct) return;
        addToBag(selectedProduct);
        showToast(`${selectedProduct.dataset.product} added to bag`);
        closeDetails();
    });
}

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && productModal && productModal.classList.contains('is-open')) {
        closeDetails();
    }
});

const params = new URLSearchParams(window.location.search);
if (params.get('payment') === 'success') {
    showToast('Order placed successfully');
}

updateBag();

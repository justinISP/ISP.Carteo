document.addEventListener('DOMContentLoaded', () => {
    renderCart();
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart')) {
        const btn = e.target;

        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const price = parseFloat(btn.dataset.price);

        addToCart(id, name, price);
    }
});

let timeout;

document.getElementById('searchInput').addEventListener('input', function() {

    clearTimeout(timeout);

    timeout = setTimeout(() => {

        const query = this.value.toLowerCase();
        const dishes = document.querySelectorAll('.order-dish');

        dishes.forEach(dish => {
            const text = (
                dish.dataset.name + ' ' + dish.dataset.description
            ).toLowerCase();

            if (text.includes(query)) {
                dish.style.display = '';
            } else {
                dish.style.display = 'none';
            }
        });

    }, 200);

});

let cart = JSON.parse(localStorage.getItem('cart')) || {};

function addToCart(id, name, price) {
    if (cart[id]) {
        cart[id].qty++;
    } else {
        cart[id] = {
            id: id,
            name: name,
            price: price,
            qty: 1
        };
    }

    saveCart();
    renderCart();
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function renderCart() {
    const cartList = document.getElementById('cartList');
    const totalPriceEl = document.getElementById('totalPrice');

    if (Object.keys(cart).length === 0) {
        cartList.innerHTML = 'Noch keine Artikel ausgewählt.';
        totalPriceEl.innerText = '0,00 €';
        return;
    }

    let total = 0;
    let html = '';

    for (let id in cart) {
        const item = cart[id];
        const itemTotal = item.price * item.qty;
        total += itemTotal;

        html += `
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                
                <div>
                    ${item.name}
                </div>

                <div style="display:flex;align-items:center;gap:6px">
                    <button onclick="decreaseQty('${id}')" style="padding:2px 6px">−</button>
                    
                    <span>${item.qty}</span>
                    
                    <button onclick="increaseQty('${id}')" style="padding:2px 6px">+</button>
                </div>

                <div style="min-width:70px;text-align:right">
                    ${(item.price * item.qty).toFixed(2).replace('.', ',')} €
                </div>

                <button onclick="removeFromCart('${id}')" style="margin-left:6px">✕</button>
            </div>
        `;

    }

    cartList.innerHTML = html;
    totalPriceEl.innerText = total.toFixed(2).replace('.', ',') + ' €';
}

function removeFromCart(id) {
    delete cart[id];
    saveCart();
    renderCart();
}

function increaseQty(id) {
    if (!cart[id]) return;

    cart[id].qty++;
    saveCart();
    renderCart();
}

function decreaseQty(id) {
    if (!cart[id]) return;

    cart[id].qty--;

    if (cart[id].qty <= 0) {
        delete cart[id];
    }

    saveCart();
    renderCart();
}

function sendWhatsApp(btn) {
    const name = document.getElementById('custName').value;
    const time = document.getElementById('pickupTime').value;
    const message = document.getElementById('custMessage').value;
    const phone = document.getElementById('phone').value;

    if (!name) {
        alert('Bitte Namen eingeben');
        return;
    }

    if (!phone) {
        alert('Bitte Telefonnummer eingeben');
        return;
    }

    if (!time) {
        alert('Bitte Abholzeit eingeben');
        return;
    }

    if (Object.keys(cart).length === 0) {
        alert('Warenkorb ist leer');
        return;
    }

    let text = `Neue Bestellung:%0A%0A`;

    for (let id in cart) {
        const item = cart[id];
        text += `${item.qty}x ${item.name}%0A`;
    }

    fetch('/api/order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            customer: {
                name: document.getElementById('custName').value,
                phone: document.getElementById('phone').value,
                pickupTime: document.getElementById('pickupTime').value,
                message: document.getElementById('custMessage').value
            },
            cart: Object.values(cart)
        })
    })
    .then(res => res.text())
    .then(data => {
        console.log('Response:', data);
    })
    .catch(err => {
        console.error('Fetch Fehler:', err);
    });

    text += `%0AGesamt: ${document.getElementById('totalPrice').innerText}%0A`;
    text += `Name: ${name}%0A`;
    text += `Abholzeit: ${time}%0A`;

    if (message) {
        text += `Nachricht: ${message}%0A`;
    }

    const dataPhone = btn.dataset.phone;

    const followUp = btn.dataset.followup;

    phoneTo = dataPhone.replace("+", "");

    const url = `https://wa.me/${phoneTo}?text=${text}`;

    window.open(url, '_blank');

    cart = {};
    localStorage.removeItem('cart');

    renderCart();

    setTimeout(() => {
        window.location.href = followUp;
    }, 300);

}


function openMenuItemDeleteModal(identifier){

    modal = document.querySelector('#delete-menu-item-modal');

    document.querySelector('#delete-menu-item-modal .neos-modal-footer button').setAttribute("data-id", identifier);

    modal.style.display = "block";

}

function closeMenuItemDeleteModal(){

    modal.style.display = "none";

}

function openMenuItemChange(span){

    form = span.nextSibling;

    span.style.display = "none";
    form.style.display = "block";

}

function revertMenuItemChange(btn){

    span = btn.parentNode.parentNode.previousSibling;
    form = btn.parentNode.parentNode;

    span.style.display = "block";
    form.style.display = "none";

}

function normalizePhoneNumber(input) {
    if (!input) return null;

    let number = input.replace(/\D/g, "");

    if (number.startsWith("0049")) {
        number = number.slice(2);
    }

    if (number.startsWith("0")) {
        number = "49" + number.slice(1);
    }

    else if (number.startsWith("49")) {}

    else {
        number = "49" + number;
    }

    return number;
}

function sendAccept(btn) {

    const pickupTime = btn.dataset.pickuptime;

    let text = `Vielen Dank für Ihre Bestellung,%0A%0A diese kann um ${pickupTime} Uhr bei uns im Ratskeller Altenburg abgeholt werden.`;

    const dataPhone = btn.dataset.customerphone;

    phoneTo = normalizePhoneNumber(dataPhone);

    const url = `https://wa.me/${phoneTo}?text=${text}`;

    window.open(url, '_blank');

}

function sendDecline(btn) {

    let text = `Vielen Dank für Ihre Bestellung,%0A%0A leider müssen wir Ihnen mitteilen, dass wir Ihre Bestellung stornieren.`;

    const dataPhone = btn.dataset.customerphone;

    phoneTo = normalizePhoneNumber(dataPhone);

    const url = `https://wa.me/${phoneTo}?text=${text}`;

    window.open(url, '_blank');

}

function toggleData(){

    card = document.querySelector('#order-card');
    arrow = document.querySelector('#order-arrow');

    arrow.classList.toggle('rotate');
    card.classList.toggle('open');

}
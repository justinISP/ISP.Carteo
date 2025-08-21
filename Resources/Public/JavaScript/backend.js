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
function openModal(title, author, category, isbn, year, avail, desc) {
    document.getElementById('m-title').innerText = title;
    document.getElementById('m-author').innerText = author;
    document.getElementById('m-category').innerText = category;
    document.getElementById('m-isbn').innerText = isbn;
    document.getElementById('m-year').innerText = year;
    document.getElementById('m-avail').innerText = avail + " / Total";
    document.getElementById('m-desc').innerText = desc;

    document.getElementById('bookModal').style.display = 'flex';
    document.body.style.overflow = 'hidden'; 
}

function closeModal() {
    document.getElementById('bookModal').style.display = 'none';
    document.body.style.overflow = 'auto'; 
}


function openEditModal(id, title, author, category, status, desc) {
    document.getElementById('e-id').value = id;
    document.getElementById('e-title').value = title;
    document.getElementById('e-author').value = author;
    document.getElementById('e-category').value = category;
    document.getElementById('e-status').value = status;
    document.getElementById('e-desc').value = desc;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    let detailsModal = document.getElementById('bookModal');
    let editModal = document.getElementById('editModal');
    

    if (event.target == detailsModal) {
        closeModal();
    }
    
    if (event.target == editModal) {
        closeEditModal();
    }
}

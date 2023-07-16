function recherche(texte) {
    const xhr = new XMLHttpRequest();
    xhr.onload = () => {
        document.getElementById('listeResultatNonAmis').innerHTML = xhr.responseText;
    };
    xhr.open("GET", "/profil/recherche/" + (texte.length > 2 ? texte : ''));
    xhr.responseType = "text";
    xhr.send();
}

document.getElementById('recherche').oninput = (event) => {
    recherche(event.target.value)
};

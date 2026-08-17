export async function renderWelcome(app) {
    app.innerHTML = `
        <div class="welcome">
            <h1>Votre journée,<br>même sans réseau.</h1>
            <p><span class="dot"></span>On prépare votre carnet…</p>
        </div>`;
}

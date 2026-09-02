(function(){const t=document.createElement("link").relList;if(t&&t.supports&&t.supports("modulepreload"))return;for(const a of document.querySelectorAll('link[rel="modulepreload"]'))s(a);new MutationObserver(a=>{for(const o of a)if(o.type==="childList")for(const i of o.addedNodes)i.tagName==="LINK"&&i.rel==="modulepreload"&&s(i)}).observe(document,{childList:!0,subtree:!0});function n(a){const o={};return a.integrity&&(o.integrity=a.integrity),a.referrerPolicy&&(o.referrerPolicy=a.referrerPolicy),a.crossOrigin==="use-credentials"?o.credentials="include":a.crossOrigin==="anonymous"?o.credentials="omit":o.credentials="same-origin",o}function s(a){if(a.ep)return;a.ep=!0;const o=n(a);fetch(a.href,o)}})();const $="pocket_token";async function D(){try{const e=window.Capacitor?.Plugins?.Preferences;if(e?.get){const{value:t}=await e.get({key:$});return t||localStorage.getItem($)}}catch{}return localStorage.getItem($)}async function T(e){localStorage.setItem($,e);try{await window.Capacitor?.Plugins?.Preferences?.set?.({key:$,value:e})}catch{}}async function Z(){localStorage.removeItem($);try{await window.Capacitor?.Plugins?.Preferences?.remove?.({key:$})}catch{}}function Y(e){try{const t=new URL(e);if(t.protocol!=="allotata:")return null;const n=t.hash.startsWith("#")?t.hash.slice(1):t.hash;return new URLSearchParams(n).get("token")}catch{return typeof e=="string"&&e.includes("token=")?decodeURIComponent(e.split("token=")[1].split("&")[0]):null}}const I="pocket_host",_={local:{id:"local",label:"Local",hint:"allotata.test",api:"https://api.allotata.test/v1",dash:"https://dash.allotata.test",settings:"https://dash.allotata.test/settings",checkout:"https://dash.allotata.test/checkout",site:"https://allotata.test",cgu:"https://allotata.test/legal/cgu",cgv:"https://allotata.test/legal/cgv",confidentialite:"https://allotata.test/legal/confidentialite"},prod:{id:"prod",label:"Production",hint:"allotata.fr",api:"https://api.allotata.fr/v1",dash:"https://dash.allotata.fr",settings:"https://dash.allotata.fr/settings",checkout:"https://dash.allotata.fr/checkout",site:"https://allotata.fr",cgu:"https://allotata.fr/legal/cgu",cgv:"https://allotata.fr/legal/cgv",confidentialite:"https://allotata.fr/legal/confidentialite"}},ee="prod";let L=_[ee];function F(){return L}async function h(){let e=ee;try{const t=window.Capacitor?.Plugins?.Preferences,n=t?.get?(await t.get({key:I})).value:localStorage.getItem(I);n&&_[n]&&(e=n)}catch{const t=localStorage.getItem(I);t&&_[t]&&(e=t)}return L=_[e],L}async function ce(e){if(!_[e])return L;L=_[e],localStorage.setItem(I,e);try{await window.Capacitor?.Plugins?.Preferences?.set?.({key:I,value:e})}catch{}return L}function le(e){return e==="prod"?"local":"prod"}function M(){const e=F();return`<button type="button" class="auth-host" id="auth-host" data-id="${e.id}">
        <span class="auth-host-dot ${e.id}"></span>
        ${e.label} · ${e.hint}
    </button>`}function P(e){document.getElementById("auth-host")?.addEventListener("click",async()=>{await ce(le(F().id)),e()})}function O(e,t={}){return{Accept:"application/json","X-Capacitor":"1","X-AlloTata-Native":"1",...t.body&&!(t.body instanceof FormData)?{"Content-Type":"application/json"}:{},...e?{Authorization:`Bearer ${e}`}:{},...t.headers||{}}}function U(e,t){const n=e.errors&&Object.values(e.errors).flat().find(Boolean),s=new Error(n||e.message||`Erreur ${t}`);return s.code=e.code,s.status=t,s.data=e,s}function V(e){if(!(e instanceof TypeError)&&!/failed to fetch|networkerror|load failed/i.test(e.message||""))return e;const t=F(),n=new Error(t.id==="prod"?"Production injoignable depuis ici. Passe en local pour tester.":"Le serveur local ne répond pas (api.allotata.test).");return n.code="reseau",n.cause=e,n}async function f(e,t={}){const n=await D(),s=await h();let a;try{a=await fetch(`${s.api}${e}`,{...t,headers:O(n,t)})}catch(o){throw V(o)}if(a.status===401)throw new Error("jeton_invalide");if(!a.ok){const o=await a.json().catch(()=>({}));throw U(o,a.status)}return(a.headers.get("content-type")||"").includes("pdf")?a.blob():a.json()}async function ue(e){const t=await h();let n;try{n=await fetch(`${t.api}${e}`,{headers:O(null)})}catch(a){throw V(a)}const s=await n.json().catch(()=>({}));if(!n.ok)throw U(s,n.status);return s}async function B(e,t){const n=await h();let s;try{s=await fetch(`${n.api}${e}`,{method:"POST",headers:O(null,{body:"{}"}),body:JSON.stringify(t)})}catch(o){throw V(o)}const a=await s.json().catch(()=>({}));if(!s.ok)throw U(a,s.status);return a}async function G(){try{await window.Capacitor?.Plugins?.SplashScreen?.hide?.()}catch{}}async function de(e){const t=window.Capacitor?.Plugins?.Browser;if(t?.open){await t.open({url:e});return}window.open(e,"_blank")}function pe(e){e&&(window.location.href=`tel:${e}`)}function me(e){e&&(window.location.href=`sms:${e}`)}function he(e){e&&(window.location.href=`geo:0,0?q=${encodeURIComponent(e)}`)}async function ge(){const e=await h();return{dash:e.dash,settings:e.settings,checkout:e.checkout,cgu:e.cgu,cgv:e.cgv,confidentialite:e.confidentialite}}function fe(e){return e?new Date(e).toLocaleTimeString("fr-FR",{hour:"2-digit",minute:"2-digit"}):"—"}function we(e){return e==="en_attente"?"wait":e==="annulee"?"no":"ok"}function c(e){return String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}function be(e){return String(e?.nom||"").trim().split(/\s+/)[0]||"toi"}function k(){return new Date().toLocaleTimeString("fr-FR",{hour:"2-digit",minute:"2-digit"})}function z(){return new Date().toLocaleDateString("fr-FR",{weekday:"long",day:"numeric",month:"long"})}function u(e){return`<nav class="nav">${[["home","Accueil","⌂"],["reservations","Réserv.","▦"],["plus","Plus","···"]].map(([n,s,a])=>`
        <button class="${e===n?"on":""}" data-go="#/${n}">
            <span class="ico">${a}</span>
            ${s}
        </button>`).join("")}</nav>`}async function te(e){await h(),e.innerHTML=`
        <div class="pitch">
            <div class="pitch-hero rise">
                <p class="auth-date">${z()}</p>
                <p class="auth-now pitch-now" id="auth-now">${k()}</p>
                <h1 class="pitch-title">Allotata</h1>
                <p class="pitch-tag">votre gestionnaire de PME et EI</p>
            </div>
            <div class="pitch-actions rise-late">
                <button class="btn primary tap" type="button" data-go="#/login">Se connecter</button>
                <button class="btn ghost tap" type="button" data-go="#/register">S’enregistrer</button>
            </div>
            ${M()}
        </div>`;const t=document.getElementById("auth-now"),n=window.setInterval(()=>{if(!document.getElementById("auth-now")){window.clearInterval(n);return}t.textContent=k()},1e4);P(()=>te(e))}function J(e,{title:t,lead:n,fields:s,submit:a,extra:o=""}){e.innerHTML=`
        <div class="auth">
            <div class="auth-hero rise">
                <p class="auth-date">${z()}</p>
                <p class="auth-now" id="auth-now">${k()}</p>
                <h1>${t}</h1>
                <p class="auth-lead">${n}</p>
            </div>
            <form id="auth-form" class="auth-card rise-late">
                ${s}
                <p class="auth-err" id="auth-err" hidden></p>
                <button class="btn primary auth-go tap" type="submit">${a}</button>
            </form>
            ${o}
            ${M()}
        </div>`}function w(e){const t=document.getElementById("auth-err");t&&(t.hidden=!e,t.textContent=e||"")}function b(e,t,n){const s=e.querySelector(".auth-go");s&&(s.disabled=t,s.innerHTML=t?`<span class="dot"></span>${n}`:s.dataset.label)}function ye(){const e=document.getElementById("auth-now");if(!e)return;const t=window.setInterval(()=>{if(!document.getElementById("auth-now")){window.clearInterval(t);return}e.textContent=k()},1e4)}async function N(e){await h(),ye(),P(e)}const H="pocket_verify_email";async function ae(e,{onReady:t,go:n}){await h();const s=sessionStorage.getItem(H)||"";J(e,{title:"Heureux de<br>vous revoir.",lead:"Votre espace membre Allotata.",fields:`<label>E-mail</label>
            <input name="email" type="email" autocomplete="username" required value="${c(s)}">
            <label>Mot de passe</label>
            <input name="password" type="password" autocomplete="current-password" required>`,submit:"Se connecter",extra:`<button type="button" class="auth-link" data-go="#/register">S’enregistrer</button>
            <button type="button" class="auth-link" data-go="#/">Retour</button>`});const a=document.getElementById("auth-form");a.querySelector(".auth-go").dataset.label="Se connecter",await N(()=>ae(e,{onReady:t,go:n})),a.addEventListener("submit",async o=>{o.preventDefault();const i=new FormData(a);w(""),b(a,!0,"On ouvre le carnet…");try{const l=await B("/auth/login",{email:i.get("email"),password:i.get("password")});if(l.jeton){b(a,!0,"C’est bon."),sessionStorage.removeItem(H),await T(l.jeton),await t();return}}catch(l){if(l.code==="a2f_requis")return ne(e,{onReady:t,go:n,challenge:l.data.challenge,methode:l.data.methode});if(l.code==="email_non_verifie"){sessionStorage.setItem(H,String(i.get("email")||"")),n("#/verify");return}w(l.message)}b(a,!1)})}async function ne(e,{onReady:t,go:n,challenge:s,methode:a}){await h(),J(e,{title:"Un dernier geste.",lead:"Juste le code, puis votre espace.",fields:`<label>${a==="totp"?"Code de l’application d’authentification":"Code reçu par e-mail ou SMS"}</label>
            <input name="code" inputmode="numeric" autocomplete="one-time-code" required maxlength="20">`,submit:"Valider",extra:a!=="totp"?'<button type="button" class="auth-link" id="auth-resend">Renvoyer le code</button>':""});const i=document.getElementById("auth-form");i.querySelector(".auth-go").dataset.label="Valider",await N(()=>ne(e,{onReady:t,go:n,challenge:s,methode:a})),document.getElementById("auth-resend")?.addEventListener("click",async()=>{try{await B("/auth/2fa/renvoyer",{challenge:s}),w("")}catch(l){w(l.message)}}),i.addEventListener("submit",async l=>{l.preventDefault();const y=new FormData(i);w(""),b(i,!0,"Vérification…");try{const E=await B("/auth/2fa",{challenge:s,code:y.get("code")});b(i,!0,"C’est bon."),await T(E.jeton),await t()}catch(E){w(E.message),b(i,!1)}})}const ve="pocket_verify_email";function q(){return{name:"",surname:"",email:"",password:"",password_confirmation:"",date_naissance:"",telephone:"",adresse:"",ville:"",code_postal:"",latitude:"",longitude:"",cgu_accepted:!1,cgv_accepted:!1,confidentialite_accepted:!1}}let r=q(),g=1;function K(e){const t=new FormData(e);for(const[n,s]of t.entries())n.endsWith("_accepted")?r[n]=!0:r[n]=String(s);g===3&&(r.cgu_accepted=!!e.querySelector('[name="cgu_accepted"]')?.checked,r.cgv_accepted=!!e.querySelector('[name="cgv_accepted"]')?.checked,r.confidentialite_accepted=!!e.querySelector('[name="confidentialite_accepted"]')?.checked)}function m(e){return c(r[e]||"")}function $e(){const e=new Date;return e.setDate(e.getDate()-1),e.toISOString().slice(0,10)}function R(){return`<div class="wizard-dots" aria-hidden="true">${[1,2,3].map(e=>`<i class="${e===g?"on":""}"></i>`).join("")}</div>`}async function S(e,{go:t,reset:n=!1}={}){n&&(r=q(),g=1),await h();const s=await ge(),o={1:{title:"Qui êtes-vous ?",lead:"Les mêmes infos que sur le site.",submit:"Continuer",fields:`${R()}
                <label>Prénom</label>
                <input name="name" autocomplete="given-name" required value="${m("name")}">
                <label>Nom</label>
                <input name="surname" autocomplete="family-name" required value="${m("surname")}">
                <label>E-mail</label>
                <input name="email" type="email" autocomplete="email" required value="${m("email")}">
                <label>Mot de passe</label>
                <input name="password" type="password" autocomplete="new-password" required minlength="8" value="${m("password")}">
                <label>Confirmation</label>
                <input name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" value="${m("password_confirmation")}">
                <label>Date de naissance</label>
                <input name="date_naissance" type="date" required max="${$e()}" value="${m("date_naissance")}">`,extra:'<button type="button" class="auth-link" data-go="#/">Retour</button>'},2:{title:"Où vous écrire ?",lead:"Téléphone et adresse, comme sur le web.",submit:"Continuer",fields:`${R()}
                <label>Téléphone</label>
                <input name="telephone" type="tel" autocomplete="tel" required maxlength="20" value="${m("telephone")}">
                <label>Adresse</label>
                <input name="adresse" id="reg-adresse" autocomplete="street-address" required value="${m("adresse")}">
                <div class="suggest" id="reg-suggest" hidden></div>
                <label>Ville</label>
                <input name="ville" id="reg-ville" autocomplete="address-level2" required value="${m("ville")}">
                <label>Code postal</label>
                <input name="code_postal" id="reg-cp" autocomplete="postal-code" required maxlength="10" value="${m("code_postal")}">
                <input type="hidden" name="latitude" id="reg-lat" value="${m("latitude")}">
                <input type="hidden" name="longitude" id="reg-lng" value="${m("longitude")}">`,extra:'<button type="button" class="auth-link" id="reg-back">Retour</button>'},3:{title:"Dernière étape.",lead:"À accepter pour créer le compte. Pas de connexion automatique.",submit:"Créer mon compte",fields:`${R()}
                <label class="check">
                    <input type="checkbox" name="cgu_accepted" value="1" ${r.cgu_accepted?"checked":""}>
                    <span>J’accepte les <button type="button" class="inline" data-web="${s.cgu}">CGU</button></span>
                </label>
                <label class="check">
                    <input type="checkbox" name="cgv_accepted" value="1" ${r.cgv_accepted?"checked":""}>
                    <span>J’accepte les <button type="button" class="inline" data-web="${s.cgv}">CGV</button></span>
                </label>
                <label class="check">
                    <input type="checkbox" name="confidentialite_accepted" value="1" ${r.confidentialite_accepted?"checked":""}>
                    <span>J’accepte la <button type="button" class="inline" data-web="${s.confidentialite}">confidentialité</button></span>
                </label>`,extra:'<button type="button" class="auth-link" id="reg-back">Retour</button>'}}[g];J(e,o);const i=document.getElementById("auth-form");i.querySelector(".auth-go").dataset.label=o.submit,await N(()=>S(e,{go:t})),document.getElementById("reg-back")?.addEventListener("click",()=>{K(i),g-=1,S(e,{go:t})}),g===2&&_e(),i.addEventListener("submit",async l=>{if(l.preventDefault(),K(i),w(""),g===1&&r.password!==r.password_confirmation){w("Les mots de passe ne correspondent pas.");return}if(g<3){g+=1,S(e,{go:t});return}if(!r.cgu_accepted||!r.cgv_accepted||!r.confidentialite_accepted){w("Acceptez les CGU, CGV et la confidentialité.");return}b(i,!0,"Création du compte…");try{const y=await B("/auth/register",{name:r.name,surname:r.surname,email:r.email,password:r.password,password_confirmation:r.password_confirmation,date_naissance:r.date_naissance,telephone:r.telephone,adresse:r.adresse,ville:r.ville,code_postal:r.code_postal,latitude:r.latitude||null,longitude:r.longitude||null,cgu_accepted:!0,cgv_accepted:!0,confidentialite_accepted:!0});sessionStorage.setItem(ve,y.email||r.email),r=q(),g=1,t("#/verify")}catch(y){w(y.message),b(i,!1)}})}function _e(){const e=document.getElementById("reg-adresse"),t=document.getElementById("reg-suggest");if(!e||!t)return;let n=0;e.addEventListener("input",()=>{window.clearTimeout(n);const s=e.value.trim();if(s.length<3){t.hidden=!0,t.innerHTML="";return}n=window.setTimeout(async()=>{try{const o=(await ue(`/address/search?q=${encodeURIComponent(s)}&limit=5`)).results||[];if(!o.length){t.hidden=!0,t.innerHTML="";return}t.hidden=!1,t.innerHTML=o.map(i=>`<button type="button" class="suggest-item" data-label="${c(i.label)}" data-city="${c(i.city)}" data-cp="${c(i.postcode)}" data-lat="${i.latitude??""}" data-lng="${i.longitude??""}">${c(i.label)}</button>`).join("")}catch{t.hidden=!0}},300)}),t.addEventListener("click",s=>{const a=s.target.closest(".suggest-item");a&&(e.value=a.dataset.label||"",document.getElementById("reg-ville").value=a.dataset.city||"",document.getElementById("reg-cp").value=a.dataset.cp||"",document.getElementById("reg-lat").value=a.dataset.lat||"",document.getElementById("reg-lng").value=a.dataset.lng||"",r.adresse=e.value,r.ville=a.dataset.city||"",r.code_postal=a.dataset.cp||"",r.latitude=a.dataset.lat||"",r.longitude=a.dataset.lng||"",t.hidden=!0,t.innerHTML="")})}const Le="pocket_verify_email";async function se(e){await h();const t=sessionStorage.getItem(Le)||"";e.innerHTML=`
        <div class="auth">
            <div class="auth-hero rise">
                <p class="auth-date">${z()}</p>
                <p class="auth-now" id="auth-now">${k()}</p>
                <h1>Vérifiez<br>votre e-mail</h1>
                <p class="auth-lead">${t?`Un message a été envoyé à <strong>${c(t)}</strong>.`:"Ouvrez le message Allotata, puis revenez ici."}</p>
            </div>
            <div class="auth-card rise-late">
                <button class="btn primary tap" type="button" data-go="#/login">J’ai vérifié</button>
                <button class="auth-link" type="button" data-go="#/login">Retour à la connexion</button>
            </div>
            ${M()}
        </div>`;const n=document.getElementById("auth-now"),s=window.setInterval(()=>{if(!document.getElementById("auth-now")){window.clearInterval(s);return}n.textContent=k()},1e4);P(()=>se(e))}async function j(e,{onAuthError:t}){e.innerHTML=`
        <header class="top"><h1>Accueil</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${u("home")}`;try{const[n,s,a,o]=await Promise.all([f("/moi"),f("/mes-reservations?par_page=1"),f("/messagerie/conversations"),f("/factures?par_page=1")]),i=n.compte||{},l=s.pagination?.total??(s.donnees||[]).length,y=(a.donnees||[]).length,E=o.pagination?.total??(o.donnees||[]).length;e.innerHTML=`
            <header class="hello rise">
                <p class="hello-kicker">Espace membre</p>
                <h1>Bonjour, ${c(be(i))}</h1>
            </header>
            <div class="tiles rise-late">
                <button class="tile tap" type="button" data-go="#/reservations">
                    <span>Réservations</span>
                    <strong>${l}</strong>
                </button>
                <button class="tile tap" type="button" data-go="#/messages">
                    <span>Messages</span>
                    <strong>${y}</strong>
                </button>
                <button class="tile tap" type="button" data-go="#/factures">
                    <span>Factures</span>
                    <strong>${E}</strong>
                </button>
            </div>
            ${u("home")}`}catch(n){if(n.message==="jeton_invalide"){await t();return}e.innerHTML=`
            <header class="hello"><h1>Accueil</h1></header>
            <p class="empty">${c(n.message)}</p>
            ${u("home")}`}}const ke={en_attente:"En attente",confirmee:"Confirmée",annulee:"Annulée"};function Ee(e){return e?`${new Date(e).toLocaleDateString("fr-FR",{weekday:"short",day:"numeric",month:"short"})} · ${fe(e)}`:"—"}async function Ie(e,{onAuthError:t}){e.innerHTML=`
        <header class="top"><h1>Réservations</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${u("reservations")}`;try{const s=(await f("/mes-reservations?par_page=50")).donnees||[];e.innerHTML=`
            <header class="top"><h1>Réservations</h1></header>
            <div class="list">${s.map(a=>`
                <div class="card ${we(a.statut)}">
                    <strong>${c(a.entreprise_nom||a.service?.nom||"Réservation")}</strong>
                    <span>${c(Ee(a.date_debut))}</span>
                    <span>${c(ke[a.statut]||a.statut||"")}${a.service?.nom?` · ${c(a.service.nom)}`:""}</span>
                </div>`).join("")||'<p class="empty">Aucune réservation pour le moment.</p>'}</div>
            ${u("reservations")}`}catch(n){if(n.message==="jeton_invalide"){await t();return}e.innerHTML=`
            <header class="top"><h1>Réservations</h1></header>
            <p class="empty">${c(n.message)}</p>
            ${u("reservations")}`}}async function oe(e,{go:t,onAuthError:n}){await h();let s={};try{s=(await f("/moi")).compte||{}}catch(a){if(a.message==="jeton_invalide"){await n();return}}e.innerHTML=`
        <header class="top"><h1>Plus</h1></header>
        <div class="sheet">
            <p class="plus-name">${c(s.nom||"Compte")}</p>
            <p class="meta">${c(s.email||"")}</p>
            <p class="plus-role">Espace membre</p>
            ${M()}
            <button class="btn danger tap" type="button" id="logout">Déconnexion</button>
        </div>
        ${u("plus")}`,P(()=>oe(e,{go:t,onAuthError:n})),document.getElementById("logout")?.addEventListener("click",async()=>{await Z(),t("#/")})}async function Se(e,{onAuthError:t}){e.innerHTML=`
        <header class="top"><button data-go="#/home">‹</button><h1>Factures</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${u("home")}`;try{const s=(await f("/factures?par_page=50")).donnees||[];e.innerHTML=`
            <header class="top"><button data-go="#/home">‹</button><h1>Factures</h1></header>
            <div class="list">${s.map(a=>`
                <button type="button" data-pdf="${a.id}">
                    <strong>${c(a.numero||"Facture")}</strong>
                    <span class="meta">${c(a.montant_ttc!=null?`${a.montant_ttc} €`:"")}${a.date_facture?` · ${c(a.date_facture)}`:""}</span>
                </button>`).join("")||'<p class="empty">Aucune facture pour le moment.</p>'}</div>
            ${u("home")}`}catch(n){if(n.message==="jeton_invalide"){await t();return}e.innerHTML=`
            <header class="top"><button data-go="#/home">‹</button><h1>Factures</h1></header>
            <p class="empty">${c(n.message)}</p>
            ${u("home")}`}}async function Ce(e,t,{onAuthError:n}){if(t)return Te(e,t,{onAuthError:n});e.innerHTML=`
        <header class="top"><button data-go="#/home">‹</button><h1>Messages</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${u("home")}`;try{const a=(await f("/messagerie/conversations")).donnees||[];e.innerHTML=`
            <header class="top"><button data-go="#/home">‹</button><h1>Messages</h1></header>
            <div class="list">${a.map(o=>`
                <button type="button" data-go="#/messages/${o.id}">
                    <strong>${c(o.entreprise_nom||o.client_nom||"Conversation")}</strong>
                    <span class="meta">${c(o.dernier_message||"")}</span>
                </button>`).join("")||'<p class="empty">Aucun message pour le moment.</p>'}</div>
            ${u("home")}`}catch(s){if(s.message==="jeton_invalide"){await n();return}e.innerHTML=`
            <header class="top"><button data-go="#/home">‹</button><h1>Messages</h1></header>
            <p class="empty">${c(s.message)}</p>
            ${u("home")}`}}async function Te(e,t,{onAuthError:n}){e.innerHTML=`
        <header class="top"><button data-go="#/messages">‹</button><h1>Conversation</h1></header>
        <p class="empty"><span class="dot"></span>Chargement…</p>
        ${u("home")}`;try{const a=(await f(`/messagerie/conversations/${t}/messages`)).donnees||[];e.innerHTML=`
            <header class="top"><button data-go="#/messages">‹</button><h1>Conversation</h1></header>
            ${a.map(o=>`<div class="msg">${c(o.contenu||"")}</div>`).join("")||'<p class="empty">Pas de messages.</p>'}
            ${u("home")}`}catch(s){if(s.message==="jeton_invalide"){await n();return}e.innerHTML=`
            <header class="top"><button data-go="#/messages">‹</button><h1>Conversation</h1></header>
            <p class="empty">${c(s.message)}</p>
            ${u("home")}`}}const d=document.getElementById("app"),A=new Set(["","pitch","login","register","verify"]),re=new Set(["home","reservations","plus","messages","factures"]);let W="";function x(){const t=(location.hash||"#/").replace(/^#/,"").split("/").filter(Boolean);return{name:t[0]||"",id:t[1]||null}}function p(e){location.hash=e}function X(e){document.body.classList.toggle("guest",e)}async function ie(){await Z(),p("#/"),await v()}async function v(){const e=await D(),{name:t,id:n}=x(),s=W;W=t||(e?"home":"pitch");const a={go:p,onAuthError:ie};return e?(X(!1),!t||A.has(t)?(p("#/home"),j(d,a)):t==="reservations"?Ie(d,a):t==="plus"?oe(d,a):t==="messages"?Ce(d,n,a):t==="factures"?Se(d,a):(t==="home"||re.has(t)||p("#/home"),j(d,a))):(X(!0),t==="login"?ae(d,{go:p,onReady:async()=>{p("#/home"),await v()}}):t==="register"?S(d,{go:p,reset:s!=="register"}):t==="verify"?se(d):(t&&!A.has(t)&&p("#/"),te(d)))}function Be(){d.addEventListener("click",e=>{const t=e.target.closest("[data-go]");if(t){p(t.dataset.go);return}const n=e.target.closest("[data-call]");if(n){pe(n.dataset.call);return}const s=e.target.closest("[data-sms]");if(s){me(s.dataset.sms);return}const a=e.target.closest("[data-map]");if(a){he(a.dataset.map);return}const o=e.target.closest("[data-web]");if(o){e.preventDefault(),e.stopPropagation(),de(o.dataset.web);return}const i=e.target.closest("[data-pdf]");i&&Me(i.dataset.pdf)})}async function Me(e){if(navigator.onLine)try{const t=await f(`/factures/${e}/pdf`),n=URL.createObjectURL(t);window.open(n,"_blank")}catch(t){t.message==="jeton_invalide"&&await ie()}}function Q(e){const t=document.getElementById("boot-status");t&&(t.textContent=e)}async function C(){document.getElementById("boot")?.classList.add("out"),await G(),window.setTimeout(()=>document.getElementById("boot")?.remove(),400)}async function Pe(){Be(),await h(),await G(),window.addEventListener("hashchange",v);const e=window.Capacitor?.Plugins?.App;e?.addListener?.("appUrlOpen",async({url:s})=>{const a=Y(s);a&&(await T(a),Q("C’est bon."),p("#/home"),await v(),await C())});const t=await e?.getLaunchUrl?.();if(t?.url){const s=Y(t.url);s&&await T(s)}if(!await D()){A.has(x().name)||p("#/"),await v(),await C();return}Q("Ouverture de votre espace…"),re.has(x().name)||p("#/home"),await v(),await C()}Pe().catch(async e=>{console.error(e);try{p("#/"),await v(),await C()}catch{await G(),d.innerHTML='<div class="auth"><h1>Allotata n’a pas pu s’ouvrir.</h1><p class="auth-lead">Relance l’app. Si ça continue, réinstalle l’APK.</p></div>'}});

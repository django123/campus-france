/* ==========================================================================
   Campus Connect — chrome commun des wireframes
   --------------------------------------------------------------------------
   Injecte le header, le footer et le bouton flottant WhatsApp sur chaque page
   maquettée, pour que la structure reste identique partout et ne soit décrite
   qu'une fois.

   Les gabarits sont des chaînes JS et non des fichiers chargés en fetch() :
   les wireframes doivent s'ouvrir par double-clic (file://), où fetch() est
   bloqué par la politique d'origine.

   Usage dans une page :
     <body data-page="Accueil">  ... contenu ...  <script src="wireframe.js">
   ========================================================================== */

(function () {
  "use strict";

  /* Menu principal. « bientôt » = destinations annoncées mais pas ouvertes,
     cohérent avec le CPT `destination` et son statut actif / bientôt. */
  var NAV = [
    { label: "Accueil" },
    { label: "Qui sommes-nous" },
    { label: "Pourquoi nous" },
    {
      label: "Destinations",
      children: [
        { label: "France" },
        { label: "Canada", tag: "bientôt" },
        { label: "Allemagne", tag: "bientôt" }
      ]
    },
    { label: "Formations" },
    { label: "Services" },
    { label: "Accompagnements" },
    { label: "Comment ça marche" },
    { label: "Partenaires" },
    { label: "Blog" },
    { label: "Contact" }
  ];

  function navItem(item) {
    var sub = "";

    if (item.children) {
      sub =
        '<ul class="wf-submenu" data-label="sous-menu">' +
        item.children
          .map(function (child) {
            var tag = child.tag
              ? ' <span class="wf-tag">' + child.tag + "</span>"
              : "";
            return '<li><a href="#">' + child.label + tag + "</a></li>";
          })
          .join("") +
        "</ul>";
    }

    return (
      '<li class="wf-nav__item"><a href="#">' +
      item.label +
      (item.children ? " ▾" : "") +
      "</a>" +
      sub +
      "</li>"
    );
  }

  function headerHTML() {
    return (
      '<header class="wf-header" data-label="header">' +
        '<div class="wf-header__inner">' +
          '<div class="wf-logo-ph" title="Logo Campus Connect"></div>' +
          '<ul class="wf-nav">' + NAV.map(navItem).join("") + "</ul>" +
          '<a class="wf-btn wf-btn--primary wf-btn--sm" href="#">' +
            "Je veux étudier en France</a>" +
          '<button class="wf-burger" type="button" aria-label="Menu" ' +
            'aria-expanded="false">☰</button>' +
        "</div>" +
      "</header>" +

      '<div class="wf-container" style="padding-top:16px">' +
        '<p class="note">Menu : onze entrées plus le bouton CTA ne tiennent ' +
          "pas sur une seule ligne à 1280 px — la barre se replie sur deux " +
          "lignes. Soit on regroupe (par exemple Formations, Services et " +
          "Accompagnements sous une même entrée « Nos offres »), soit on " +
          "assume les deux lignes. À trancher avant la maquette haute " +
          "fidélité.</p>" +
      "</div>"
    );
  }

  function pagebarHTML(page) {
    return (
      '<div class="wf-pagebar">' +
        '<div class="wf-pagebar__inner">' +
          "<span>Wireframe basse fidélité — " + page + "</span>" +
          '<a href="index.html">← Toutes les pages</a>' +
        "</div>" +
      "</div>"
    );
  }

  function footerHTML() {
    return (
      '<footer class="wf-footer" data-label="footer">' +
        '<div class="wf-container">' +
          '<div class="wf-footer__cols">' +

            '<div class="wf-card" data-label="coordonnées france">' +
              '<p class="wf-h3">Campus Connect France</p>' +
              "<ul>" +
                "<li>Adresse — ligne 1</li>" +
                "<li>Code postal, ville</li>" +
                "<li>Téléphone</li>" +
                "<li>E-mail</li>" +
              "</ul>" +
            "</div>" +

            '<div class="wf-card" data-label="coordonnées cameroun">' +
              '<p class="wf-h3">Campus Connect Cameroun</p>' +
              "<ul>" +
                "<li>Adresse — ligne 1</li>" +
                "<li>Ville</li>" +
                "<li>Téléphone / WhatsApp</li>" +
                "<li>E-mail</li>" +
              "</ul>" +
            "</div>" +

            '<div class="wf-card" data-label="liens rapides">' +
              '<p class="wf-h3">Liens rapides</p>' +
              "<ul>" +
                '<li><a href="#">Qui sommes-nous</a></li>' +
                '<li><a href="#">Destinations</a></li>' +
                '<li><a href="#">Accompagnements</a></li>' +
                '<li><a href="#">Comment ça marche</a></li>' +
                '<li><a href="#">Partenaires</a></li>' +
                '<li><a href="#">Blog</a></li>' +
                '<li><a href="#">Contact</a></li>' +
              "</ul>" +
            "</div>" +

            '<div class="wf-card" data-label="liens légaux">' +
              '<p class="wf-h3">Informations légales</p>' +
              "<ul>" +
                '<li><a href="#">Mentions légales</a></li>' +
                '<li><a href="#">CGV</a></li>' +
                '<li><a href="#">Politique de confidentialité</a></li>' +
                '<li><a href="#">Gestion des cookies</a></li>' +
              "</ul>" +
            "</div>" +

          "</div>" +

          '<div class="wf-footer__bottom">' +
            '<span class="wf-small">© Campus Connect — tous droits réservés</span>' +
            '<div class="wf-social">' +
              "<span>FB</span><span>IG</span><span>IN</span>" +
              "<span>TT</span><span>YT</span>" +
            "</div>" +
          "</div>" +

          '<p class="note">Footer : le lien « Gestion des cookies » est ajouté ' +
            "pour rappeler l'obligation RGPD d'un retrait du consentement aussi " +
            "simple que son recueil. À confirmer avec la configuration retenue " +
            "du bandeau de consentement.</p>" +

          '<p class="note">Réseaux sociaux : cinq emplacements sont réservés. ' +
            "Indiquer lesquels sont réellement actifs et alimentés — mieux vaut " +
            "deux comptes vivants que cinq icônes mortes.</p>" +

        "</div>" +
      "</footer>"
    );
  }

  function whatsappHTML() {
    return '<a class="wf-whatsapp" href="#">💬 WhatsApp</a>';
  }

  function mount() {
    var page = document.body.getAttribute("data-page") || "Sans titre";

    document.body.insertAdjacentHTML("afterbegin", headerHTML());
    document.body.insertAdjacentHTML("afterbegin", pagebarHTML(page));
    document.body.insertAdjacentHTML("beforeend", footerHTML());
    document.body.insertAdjacentHTML("beforeend", whatsappHTML());

    var header = document.querySelector(".wf-header");
    var burger = document.querySelector(".wf-burger");

    burger.addEventListener("click", function () {
      var open = header.classList.toggle("is-open");
      burger.setAttribute("aria-expanded", open ? "true" : "false");
    });

    mountTabs();
  }

  /* Onglets génériques : un conteneur [data-tabs] regroupe des boutons
     [data-tab] et des panneaux [data-panel] appariés par leur valeur. */
  function mountTabs() {
    var groups = document.querySelectorAll("[data-tabs]");

    Array.prototype.forEach.call(groups, function (group) {
      var buttons = group.querySelectorAll("[data-tab]");
      var panels = group.querySelectorAll("[data-panel]");

      function select(name) {
        Array.prototype.forEach.call(buttons, function (b) {
          b.setAttribute("aria-selected", b.dataset.tab === name ? "true" : "false");
        });
        Array.prototype.forEach.call(panels, function (p) {
          p.hidden = p.dataset.panel !== name;
        });
      }

      Array.prototype.forEach.call(buttons, function (b) {
        b.addEventListener("click", function () { select(b.dataset.tab); });
      });

      if (buttons.length) { select(buttons[0].dataset.tab); }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount);
  } else {
    mount();
  }
})();

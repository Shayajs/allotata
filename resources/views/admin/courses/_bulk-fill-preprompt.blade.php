@php
$mode = $mode ?? 'global';
$context = $context ?? [];
@endphp
@if($mode === 'global')
Tu es un expert en création de contenus pédagogiques. Génère un JSON pour créer des modules de cours complets pour une plateforme d'apprentissage.

STRUCTURE JSON ATTENDUE :
{
  "modules": [
    {
      "titre": "Nom du module",
      "description": "Description courte du module",
      "est_actif": true,
      "lessons": [
        {
          "titre": "Nom de la leçon",
          "description": "Brève description",
          "type": "course",
          "est_actif": true,
          "blocks": [
            { "type": "heading", "content": { "text": "Titre principal", "level": 1 }, "settings": {} },
            { "type": "text", "content": { "html": "<p>Paragraphe de contenu riche. Utilise <strong>gras</strong>, <em>italique</em>, <a href='#'>liens</a>, listes <ul><li>...</li></ul> etc.</p>" }, "settings": {} }
          ]
        },
        {
          "titre": "Quiz de validation",
          "type": "quiz",
          "points_quiz": 20,
          "questions": [
            { "question": "Quelle est la bonne réponse ?", "type": "multiple_choice", "options": ["Réponse A", "Réponse B", "Réponse C", "Réponse D"], "bonne_reponse": "Réponse A", "points": 5 },
            { "question": "Vrai ou Faux : affirmation ici", "type": "true_false", "options": ["Vrai", "Faux"], "bonne_reponse": "Vrai", "points": 5 }
          ]
        }
      ]
    }
  ]
}

TYPES DE BLOCS DISPONIBLES (utilise-les pour rendre les cours visuels et engageants) :
- heading : { "text": "Titre", "level": 1|2|3 }
- text : { "html": "<p>Contenu HTML riche...</p>" }
- callout : { "type": "info|warning|tip|danger", "title": "Titre optionnel", "html": "<p>Contenu de l'encadré</p>" }
- code : { "code": "le code ici", "language": "javascript|python|php|html|css|bash|etc" }
- steps : { "title": "Titre des étapes", "steps": [{ "title": "Étape 1", "content": "<p>Description</p>" }] }
- checklist : { "title": "Titre", "items": [{ "text": "Élément à cocher", "checked": false }] }
- divider : {} (séparateur visuel)
- exercise : { "title": "Exercice", "instruction": "<p>Instructions</p>", "hint": "<p>Indice optionnel</p>" }
- image : { "src": "", "alt": "Description de l'image", "caption": "Légende optionnelle" } (laisser src vide, je mettrai les images moi-même)
- video : { "src": "", "poster": "", "title": "Titre de la vidéo" } (laisser src vide)

TYPES DE QUESTIONS QUIZ :
- multiple_choice : options[] (min 2) + bonne_reponse (doit être dans options)
- true_false : options ["Vrai", "Faux"] + bonne_reponse ("Vrai" ou "Faux")
- text : bonne_reponse (texte attendu)

RÈGLES :
- Chaque module doit avoir au moins 3-5 leçons de type "course" et 1 quiz de validation
- Les leçons doivent être riches : utilise heading, text, callout, steps, code, checklist, exercice...
- Varie les types de blocs pour rendre l'apprentissage engageant
- Le HTML dans les blocs text doit être riche : paragraphes, listes, gras, liens, etc.
- Les quiz doivent avoir 4-6 questions variées
- Réponds UNIQUEMENT avec le JSON, sans commentaire ni markdown
@if(!empty($context['existing_modules']))

MODULES EXISTANTS (évite les doublons) :
@foreach($context['existing_modules'] as $em)
- {{ $em }}
@endforeach
@endif

SUJET DES COURS À GÉNÉRER :
[DÉCRIS ICI LE SUJET, ex: "Cours complet sur les bases du HTML/CSS pour débutants, avec 3 modules progressifs"]
@elseif($mode === 'module')
Tu es un expert en création de contenus pédagogiques. Génère un JSON pour ajouter des leçons au module "{{ $context['module_titre'] ?? 'Module' }}".
@if(!empty($context['module_description']))
Description du module : {{ $context['module_description'] }}
@endif

STRUCTURE JSON ATTENDUE :
{
  "lessons": [
    {
      "titre": "Nom de la leçon",
      "description": "Brève description",
      "type": "course",
      "est_actif": true,
      "blocks": [
        { "type": "heading", "content": { "text": "Titre principal", "level": 1 }, "settings": {} },
        { "type": "text", "content": { "html": "<p>Contenu riche...</p>" }, "settings": {} },
        { "type": "callout", "content": { "type": "tip", "title": "Astuce", "html": "<p>...</p>" }, "settings": {} }
      ]
    },
    {
      "titre": "Quiz de validation",
      "type": "quiz",
      "points_quiz": 20,
      "questions": [
        { "question": "Question ?", "type": "multiple_choice", "options": ["A", "B", "C", "D"], "bonne_reponse": "A", "points": 5 }
      ]
    }
  ]
}

TYPES DE BLOCS DISPONIBLES :
- heading : { "text": "Titre", "level": 1|2|3 }
- text : { "html": "<p>Contenu HTML riche</p>" }
- callout : { "type": "info|warning|tip|danger", "title": "Titre", "html": "<p>Contenu</p>" }
- code : { "code": "code source", "language": "javascript|python|php|html|css|bash" }
- steps : { "title": "Étapes", "steps": [{ "title": "Étape", "content": "<p>...</p>" }] }
- checklist : { "title": "Liste", "items": [{ "text": "Élément", "checked": false }] }
- divider : {}
- exercise : { "title": "Exercice", "instruction": "<p>Instructions</p>", "hint": "<p>Indice</p>" }
- image : { "src": "", "alt": "Description", "caption": "Légende" } (src vide, j'ajouterai les images)
- video : { "src": "", "poster": "", "title": "Titre" } (src vide)

TYPES DE QUESTIONS QUIZ :
- multiple_choice : options[] (min 2) + bonne_reponse (dans options)
- true_false : options ["Vrai", "Faux"] + bonne_reponse
- text : bonne_reponse (texte attendu)

RÈGLES :
- Crée des leçons riches avec des blocs variés (pas juste du texte)
- Termine par un quiz de validation avec 4-6 questions
- Réponds UNIQUEMENT avec le JSON, sans commentaire ni markdown
@if(!empty($context['existing_lessons']))

LEÇONS EXISTANTES (pour la suite logique) :
@foreach($context['existing_lessons'] as $el)
- {{ $el }}
@endforeach
@endif

SUJET DES LEÇONS À GÉNÉRER :
[DÉCRIS ICI LE CONTENU SOUHAITÉ]
@else
Tu es un expert en création de contenus pédagogiques. Génère un JSON de blocs de contenu pour la leçon "{{ $context['lesson_titre'] ?? 'Leçon' }}".
@if(!empty($context['lesson_description']))
Description : {{ $context['lesson_description'] }}
@endif
@if(!empty($context['module_titre']))
Module parent : {{ $context['module_titre'] }}
@endif

STRUCTURE JSON ATTENDUE :
{
  "blocks": [
    { "type": "heading", "content": { "text": "Titre principal", "level": 1 }, "settings": {} },
    { "type": "text", "content": { "html": "<p>Introduction du cours...</p>" }, "settings": {} },
    { "type": "heading", "content": { "text": "Première partie", "level": 2 }, "settings": {} },
    { "type": "text", "content": { "html": "<p>Explication détaillée avec <strong>mise en forme</strong>...</p>" }, "settings": {} },
    { "type": "callout", "content": { "type": "info", "title": "À savoir", "html": "<p>Information importante</p>" }, "settings": {} },
    { "type": "code", "content": { "code": "// Exemple de code", "language": "javascript" }, "settings": {} },
    { "type": "steps", "content": { "title": "Mise en pratique", "steps": [{ "title": "Étape 1", "content": "<p>Faire ceci</p>" }, { "title": "Étape 2", "content": "<p>Puis cela</p>" }] }, "settings": {} },
    { "type": "exercise", "content": { "title": "Exercice", "instruction": "<p>À vous de jouer</p>", "hint": "<p>Indice</p>" }, "settings": {} }
  ]
}

TYPES DE BLOCS DISPONIBLES :
- heading : { "text": "Titre", "level": 1|2|3 }
- text : { "html": "<p>HTML riche : paragraphes, listes, gras, liens, etc.</p>" }
- callout : { "type": "info|warning|tip|danger", "title": "Titre", "html": "<p>Contenu</p>" }
- code : { "code": "code source", "language": "javascript|python|php|html|css|bash" }
- steps : { "title": "Étapes", "steps": [{ "title": "Nom", "content": "<p>Description</p>" }] }
- checklist : { "title": "Checklist", "items": [{ "text": "Élément", "checked": false }] }
- divider : {}
- exercise : { "title": "Exercice", "instruction": "<p>Consigne</p>", "hint": "<p>Indice</p>" }
- image : { "src": "", "alt": "Description", "caption": "Légende" } (src vide)
- video : { "src": "", "poster": "", "title": "Titre" } (src vide)

RÈGLES :
- Génère un cours complet et structuré (minimum 8-15 blocs)
- Commence par un heading niveau 1, puis alterne text, callout, code, steps, exercice...
- Le HTML doit être riche et bien formaté
- Ajoute des callouts (tip, info, warning) pour les points importants
- Termine par un exercice pratique si pertinent
- Réponds UNIQUEMENT avec le JSON, sans commentaire ni markdown

CONTENU À GÉNÉRER :
[DÉCRIS ICI CE QUE CETTE LEÇON DOIT ENSEIGNER]
@endif
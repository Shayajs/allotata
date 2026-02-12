# Types de Stock Proposés

## Types de stock actuels

1. **`disponible_immediatement`** : Stock géré, décrément automatique
   - Le produit a un stock physique
   - Décrément automatique lors de l'acceptation de commande
   - Alerte quand stock < seuil minimum

2. **`en_attente_commandes`** : Pas de stock, commande puis achat
   - Pas de stock physique
   - L'entreprise commande après réception de la commande client
   - Permet de voir combien de commandes en attente

## Types de stock proposés (extensions possibles)

### 3. **`precommande`** : Produit disponible en précommande
   - Produit pas encore disponible mais peut être commandé
   - Date de disponibilité prévue
   - Permet de gérer les précommandes avec date limite
   - Utile pour : produits saisonniers, nouveaux produits, etc.

### 4. **`sur_mesure`** : Produit fait sur mesure
   - Produit créé spécifiquement pour le client
   - Délai de fabrication à définir
   - Pas de stock physique
   - Permet de gérer les délais de réalisation
   - Utile pour : vêtements sur mesure, bijoux personnalisés, etc.

### 5. **`location`** : Produit en location
   - Produit disponible en location (pas de vente)
   - Gestion de la durée de location
   - Gestion du retour
   - Utile pour : matériel, équipements, etc.

### 6. **`abonnement`** : Produit avec abonnement récurrent
   - Produit avec livraison/renouvellement automatique
   - Gestion de la fréquence (hebdomadaire, mensuelle, etc.)
   - Utile pour : box mensuelle, produits récurrents, etc.

### 7. **`stock_virtuel`** : Stock virtuel (dropshipping)
   - Stock géré par un fournisseur externe
   - Synchronisation avec le fournisseur
   - Décrément automatique mais pas de stock physique local
   - Utile pour : dropshipping, produits fournis par un tiers

### 8. **`stock_illimite`** : Stock illimité
   - Produit toujours disponible
   - Pas de gestion de quantité
   - Utile pour : produits numériques, services, etc.

## Recommandation d'implémentation

Pour l'instant, garder les 2 types actuels et ajouter progressivement :
- **`precommande`** : Très utile pour beaucoup d'entreprises
- **`sur_mesure`** : Utile pour les artisans

Les autres types peuvent être ajoutés plus tard selon les besoins réels des utilisateurs.

## Structure de la base de données

Pour supporter ces nouveaux types, on pourrait :
1. Garder `gestion_stock` comme enum avec les valeurs actuelles
2. Ajouter un champ `type_stock_etendu` (nullable) pour les types avancés
3. Ou modifier `gestion_stock` pour accepter plus de valeurs

Recommandation : Modifier l'enum pour accepter les nouveaux types progressivement.

<?php
$page_title = "Tableau de bord";
include 'includes/header.php';

require_once 'php/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
$borrowed_books_stmt = $pdo->prepare("SELECT bb.*, b.title, b.author, b.cover_image 
                                    FROM borrowed_books bb 
                                    JOIN books b ON bb.book_id = b.id 
                                    WHERE bb.user_id = ? AND bb.status = 'borrowed' 
                                    ORDER BY bb.due_date ASC");
$borrowed_books_stmt->execute([$user_id]);
$borrowed_books = $borrowed_books_stmt->fetchAll(PDO::FETCH_ASSOC);
$purchases_stmt = $pdo->prepare("SELECT p.*, b.title, b.author, b.cover_image 
                               FROM purchases p 
                               JOIN books b ON p.book_id = b.id 
                               WHERE p.user_id = ? 
                               ORDER BY p.purchase_date DESC 
                               LIMIT 10");
$purchases_stmt->execute([$user_id]);
$purchases = $purchases_stmt->fetchAll(PDO::FETCH_ASSOC);
$wishlist_stmt = $pdo->prepare("SELECT w.*, b.title, b.author, b.price, b.cover_image, b.available_copies 
                              FROM wishlist w 
                              JOIN books b ON w.book_id = b.id 
                              WHERE w.user_id = ? 
                              ORDER BY w.added_at DESC");
$wishlist_stmt->execute([$user_id]);
$wishlist = $wishlist_stmt->fetchAll(PDO::FETCH_ASSOC);
$section = $_GET['section'] ?? 'overview';
?>

<section class="dashboard">
    <div class="container">
        <h1 class="section-title">Tableau de bord</h1>
        
        <div class="dashboard-grid">
            <div class="dashboard-sidebar">
                <div class="user-info">
                    <h3>Bonjour, <?php echo htmlspecialchars($user['username']); ?>!</h3>
                </div>
                
                <ul class="dashboard-nav">
                    <li><a href="?section=overview" class="<?php echo $section == 'overview' ? 'active' : ''; ?>">Vue d'ensemble</a></li>
                    <li><a href="?section=borrowed" class="<?php echo $section == 'borrowed' ? 'active' : ''; ?>">Livres empruntés</a></li>
                    <li><a href="?section=purchases" class="<?php echo $section == 'purchases' ? 'active' : ''; ?>">Historique d'achats</a></li>
                    <li><a href="?section=wishlist" class="<?php echo $section == 'wishlist' ? 'active' : ''; ?>">Liste de souhaits</a></li>
                    <li><a href="?section=settings" class="<?php echo $section == 'settings' ? 'active' : ''; ?>">Paramètres</a></li>
                </ul>
            </div>
            <div class="dashboard-content">
                <?php switch($section): 
                    case 'overview': ?>
                        <div class="dashboard-overview">
                            <div class="stats-cards">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo count($borrowed_books); ?></div>
                                    <div class="stat-label">Livres empruntés</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo count($purchases); ?></div>
                                    <div class="stat-label">Achats effectués</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo count($wishlist); ?></div>
                                    <div class="stat-label">Dans la liste de souhaits</div>
                                </div>
                            </div>
                            <div class="section">
                                <h2>Livres empruntés en cours</h2>
                                <?php if (empty($borrowed_books)): ?>
                                    <p class="no-data">Aucun livre emprunté pour le moment.</p>
                                    <a href="books.php" class="btn">Découvrir des livres</a>
                                <?php else: ?>
                                    <div class="books-list">
                                        <?php foreach ($borrowed_books as $borrowed): ?>
                                            <div class="borrowed-book-card">
                                                <img src="images/<?php echo $borrowed['cover_image'] ?: 'default.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($borrowed['title']); ?>"
                                                     class="book-cover-small">
                                                <div class="book-info">
                                                    <h4><?php echo htmlspecialchars($borrowed['title']); ?></h4>
                                                    <p><?php echo htmlspecialchars($borrowed['author']); ?></p>
                                                    <div class="due-date <?php echo strtotime($borrowed['due_date']) < time() ? 'overdue' : ''; ?>">
                                                        À retourner avant: <?php echo date('d/m/Y', strtotime($borrowed['due_date'])); ?>
                                                        <?php if (strtotime($borrowed['due_date']) < time()): ?>
                                                            <span class="penalty">Pénalité: <?php echo number_format(calculatePenalty($borrowed['due_date']), 2); ?> DT</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php break; case 'borrowed': ?>
                        <div class="section">
                            <h2>Livres empruntés</h2>
                            <?php if (empty($borrowed_books)): ?>
                                <p class="no-data">Aucun livre emprunté.</p>
                                <a href="books.php" class="btn">Parcourir le catalogue</a>
                            <?php else: ?>
                                <div class="books-grid">
                                    <?php foreach ($borrowed_books as $borrowed): ?>
                                        <div class="book-card">
                                            <img src="images/<?php echo $borrowed['cover_image'] ?: 'default.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($borrowed['title']); ?>">
                                            <div class="book-info">
                                                <h4><?php echo htmlspecialchars($borrowed['title']); ?></h4>
                                                <p><?php echo htmlspecialchars($borrowed['author']); ?></p>
                                                <p>Emprunté le: <?php echo date('d/m/Y', strtotime($borrowed['borrow_date'])); ?></p>
                                                <p>À retourner avant: <?php echo date('d/m/Y', strtotime($borrowed['due_date'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php break; case 'purchases': ?>
                        <div class="section">
                            <h2>Historique d'achats</h2>
                            <?php if (empty($purchases)): ?>
                                <p class="no-data">Aucun achat effectué.</p>
                                <a href="books.php" class="btn">Découvrir notre catalogue</a>
                            <?php else: ?>
                                <div class="purchases-list">
                                    <?php foreach ($purchases as $purchase): ?>
                                        <div class="purchase-card">
                                            <img src="images/<?php echo $purchase['cover_image'] ?: 'default.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($purchase['title']); ?>">
                                            <div class="purchase-info">
                                                <h4><?php echo htmlspecialchars($purchase['title']); ?></h4>
                                                <p><?php echo htmlspecialchars($purchase['author']); ?></p>
                                                <p class="purchase-date">Acheté le: <?php echo date('d/m/Y', strtotime($purchase['purchase_date'])); ?></p>
                                                <p class="purchase-amount"><?php echo number_format($purchase['total_amount'], 2); ?> DT</p>
                                            </div>
                                            <div class="purchase-status <?php echo $purchase['status']; ?>">
                                                <?php echo ucfirst($purchase['status']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php break; case 'wishlist': ?>
                        <div class="section">
                            <h2>Liste de souhaits</h2>
                            <?php if (empty($wishlist)): ?>
                                <p class="no-data">Votre liste de souhaits est vide.</p>
                                <a href="books.php" class="btn">Explorer les livres</a>
                            <?php else: ?>
                                <div class="books-grid">
                                    <?php foreach ($wishlist as $item): ?>
                                        <div class="book-card">
                                            <img src="images/<?php echo $item['cover_image'] ?: 'default.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                                            <div class="book-info">
                                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                                <p><?php echo htmlspecialchars($item['author']); ?></p>
                                                <div class="price"><?php echo number_format($item['price'], 2); ?> DT</div>
                                                <div class="availability <?php echo $item['available_copies'] > 0 ? 'available' : 'unavailable'; ?>">
                                                    <?php echo $item['available_copies'] > 0 ? 'Disponible' : 'Indisponible'; ?>
                                                </div>
                                                <div class="actions">
                                                    <a href="book-details.php?id=<?php echo $item['book_id']; ?>" class="btn">Voir détails</a>
                                                    <form action="php/process/wishlist-process.php" method="POST" style="display: inline;">
                                                        <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                                        <input type="hidden" name="action" value="remove">
                                                        <button type="submit" class="btn btn-outline">Retirer</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php break; case 'settings': ?>
                        <div class="section">
                            <h2>Paramètres du compte</h2>
                            <form action="php/process/update-profile.php" method="POST" class="settings-form">
                                <div class="form-group">
                                    <label for="username">Nom d'utilisateur</label>
                                    <input type="text" id="username" name="username" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Téléphone</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Adresse</label>
                                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn">Mettre à jour le profil</button>
                            </form>

                            <div class="change-password">
                                <h3>Changer le mot de passe</h3>
                                <form action="php/process/change-password.php" method="POST">
                                    <div class="form-group">
                                        <label for="current_password">Mot de passe actuel</label>
                                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password">Nouveau mot de passe</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_new_password">Confirmer le nouveau mot de passe</label>
                                        <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required>
                                    </div>
                                    
                                    <button type="submit" class="btn">Changer le mot de passe</button>
                                </form>
                            </div>
                        </div>

                <?php endswitch; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
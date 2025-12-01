<?php
$page_title = "Catalogue des livres";
include 'includes/header.php';

require_once 'php/functions.php';
$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? '';
$author = $_GET['author'] ?? '';
$sort = $_GET['sort'] ?? 'title';
$sql = "SELECT b.*, c.name as category_name FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category_id)) {
    $sql .= " AND b.category_id = ?";
    $params[] = $category_id;
}

if (!empty($author)) {
    $sql .= " AND b.author LIKE ?";
    $params[] = "%$author%";
}
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY b.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY b.price DESC";
        break;
    case 'author':
        $sql .= " ORDER BY b.author";
        break;
    case 'year':
        $sql .= " ORDER BY b.published_year DESC";
        break;
    default:
        $sql .= " ORDER BY b.title";
}

// Pagination
$books_per_page = 12;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $books_per_page;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$total_books = $stmt->rowCount();

$sql .= " LIMIT $offset, $books_per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="catalog">
    <div class="container">
        <h1 class="section-title">Catalogue des livres</h1>
        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Rechercher un livre..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-icon">🔍</button>
                    </div>
                    <div class="filter-group">
                        <select name="category" onchange="this.form.submit()">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="sort" onchange="this.form.submit()">
                            <option value="title" <?php echo $sort == 'title' ? 'selected' : ''; ?>>Trier par titre</option>
                            <option value="author" <?php echo $sort == 'author' ? 'selected' : ''; ?>>Trier par auteur</option>
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                            <option value="year" <?php echo $sort == 'year' ? 'selected' : ''; ?>>Plus récents</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="results-info">
            <p><?php echo $total_books; ?> livre(s) trouvé(s)</p>
        </div>
        <div class="books-grid">
            <?php if (empty($books)): ?>
                <div class="no-results">
                    <p>Aucun livre trouvé avec ces critères de recherche.</p>
                </div>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <div class="book-card fade-in">
                        <img src="images/<?php echo $book['cover_image'] ?: 'default.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>" 
                             class="book-cover">
                        <div class="book-info">
                            <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="book-category"><?php echo htmlspecialchars($book['category_name']); ?></p>
                            <div class="book-price"><?php echo number_format($book['price'], 2); ?> DT</div>
                            <div class="availability <?php echo $book['available_copies'] > 0 ? 'available' : 'unavailable'; ?>">
                                <?php echo $book['available_copies'] > 0 ? 'Disponible' : 'Indisponible'; ?>
                            </div>
                            <a href="book-details.php?id=<?php echo $book['id']; ?>" class="btn">Voir détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($total_books > $books_per_page): ?>
            <div class="pagination">
                <?php
                $total_pages = ceil($total_books / $books_per_page);
                for ($i = 1; $i <= $total_pages; $i++):
                ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include 'includes/footer.php'; ?>

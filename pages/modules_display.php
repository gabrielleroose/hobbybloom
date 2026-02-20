<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beginner Cooking</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body>

    <?php 
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1); //debugging/error messages
        error_reporting(E_ALL);
        
        session_start(); // NOTE: session_start(); allows access to $_SESSION variable, which can store data persistantly across pages.
        require_once __DIR__ . '/../vendor/autoload.php';

        require_once __DIR__ . '/../config/db.php'; //necessary to connect to db.

        require_once __DIR__ . '/../config/twig.php'; //necessary to load twig
        include 'base.php';

        $googleId = $_SESSION['google_id'] ?? null;

        if (!$googleId) {                   //checking if google id present, sending back to index.php if not.
        header('Location: index.php');
        exit;
}


    try {
        $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    } catch (PDOException $e) {
        die("database connection failed: " . $e->getMessage());
    }


    $all_module_ids = "SELECT id from module";
    $stmt = $pdo->prepare($all_module_ids);
    $stmt->execute();
    $module_ids_array = $stmt->fetchALL(PDO::FETCH_ASSOC);
    

    

    //!!!NOTE: WE NEED TO ADD A MODULES_DISPLAY.TWIG FILE SO THAT WE CAN PROPERLY PASS THROUGH THE SELECTED MID!!!






    
    include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
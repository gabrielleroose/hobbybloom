<?php 
require_once 'db.php'; 

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$stmt = $conn->prepare("
    SELECT u.age, p.gender, p.hometown, p.bio, p.hobbies 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$currentHobbies = [];
if (!empty($user['hobbies'])) {
    $currentHobbies = explode(', ', $user['hobbies']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link href="../css/style.css" rel="stylesheet"> 
    <link href="../css/nav.css" rel="stylesheet">
</head>

<body>

    <?php include 'base.php'; ?>

    <div class="page-container">
        <h1 style="text-align: center; margin-bottom: 30px;">Edit Your Profile</h1>

        <?php if (isset($_GET['success'])): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                Profile updated successfully!
            </div>
        <?php endif; ?>

        <section class="form" style="max-width: 800px; margin: 0 auto;">
            
            <form id="accountForm" action="update_account.php" method="post">
                
                <div class="index-form-input">
                    <label for="gender" class="form-label">Identity / Pronouns</label>
                    <input type="text" class="form-control" name="gender" id="gender" 
                           value="<?= htmlspecialchars($user['gender'] ?? '') ?>" required>
                </div>
                
                <div class="index-form-input">
                    <label for="age" class="form-label">Age</label>
                    <input type="number" class="form-control" id="age" name="age" 
                           value="<?= htmlspecialchars($user['age'] ?? '') ?>" required>
                </div>

                <div class="index-form-input">
                    <label for="from" class="form-label">Current Location</label>
                    <input type="text" class="form-control" id="from" name="from" 
                           value="<?= htmlspecialchars($user['hometown'] ?? '') ?>" required>
                </div>

                <div class="index-form-input">
                    <label for="bio" class="form-label">My Goal / Bio</label>
                    <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div class="mt-5">
                    <h3 class="form-label" style="text-align: center; margin-bottom: 20px;">Your Interests</h3>
                    <div class="hobby-grid">
                        <?php 
                        $allHobbies = ["Cooking", "Knitting", "Lego", "Sewing", "Painting", "Hiking", "Reading", "Gardening", "Baking", "Meditation", "Music", "Movies", "Gaming", "Yoga"];
                        
                        foreach ($allHobbies as $hobby) {
                            $isSelected = in_array($hobby, $currentHobbies) ? 'selected' : '';
                            echo "<div class='hobby-btn $isSelected' onclick='toggleHobby(this)'>$hobby</div>";
                        }
                        ?>
                    </div>
                </div>

                <input type="hidden" name="selected_hobbies" id="selected_hobbies_input" value="<?= htmlspecialchars($user['hobbies'] ?? '') ?>">

                <div class="mt-4 text-center">
                    <button type="button" class="index-next-button" onclick="submitAccountForm()">Save Changes</button>
                </div>
                
            </form>
        </section>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function toggleHobby(element) {
            element.classList.toggle('selected');
        }

        function submitAccountForm() {
            const selectedButtons = document.querySelectorAll('.hobby-btn.selected');
            let hobbies = [];
            selectedButtons.forEach(btn => {
                hobbies.push(btn.innerText);
            });

            document.getElementById('selected_hobbies_input').value = hobbies.join(', ');
            document.getElementById('accountForm').submit();
        }
    </script>

</body>
</html>
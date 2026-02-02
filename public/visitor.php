<?php
// public/visitor.php
require_once '../config/db.php';
require_once '../includes/functions.php';
protect();

$id = $_GET['id'] ?? null;
$vis = null;

if ($id) {
    // Edit Mode
    $stmt = $pdo->prepare("SELECT * FROM Assessment_Visitors WHERE id = ?");
    $stmt->execute([$id]);
    $vis = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $purpose = trim($_POST['purpose']);
    $status = $_POST['status'] ?? 'Signed In';
    $imagePath = $vis['image_path'] ?? null;
    $errors = [];

    // Server-side Validation
    if (empty($name))
        $errors[] = "Name is required.";
    if (empty($purpose))
        $errors[] = "Purpose is required.";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format.";
    // Basic phone validation (allow digits, spaces, +, -, ())
    if (!empty($phone) && !preg_match("/^[0-9\+\-\(\)\s]*$/", $phone))
        $errors[] = "Invalid phone format.";

    $allowedStatus = ['Signed In', 'Signed Out'];
    if (!in_array($status, $allowedStatus))
        $status = 'Signed In';

    // Handle Image upload/capture
    if (empty($errors)) {
        if (isset($_POST['camera_image']) && !empty($_POST['camera_image'])) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true); // 0755 is safer than 0777

            $data = str_replace(['data:image/png;base64,', ' '], ['', '+'], $_POST['camera_image']);
            $decoded = base64_decode($data);

            // Validate MIME type of decoded string
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($decoded);
            if ($mime === 'image/png') {
                $filename = uniqid('cam_') . '.png';
                if (file_put_contents($uploadDir . $filename, $decoded)) {
                    $imagePath = $uploadDir . $filename;
                }
            } else {
                $errors[] = "Invalid image data from camera.";
            }

        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);

            $tmp = $_FILES['image']['tmp_name'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($mime, $allowedMimes)) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                // Sanitize extension to match mime to be super safe, or just generate new one
                $exts = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp'
                ];
                $safeExt = $exts[$mime] ?? 'jpg';

                $filename = uniqid('vis_') . '.' . $safeExt;
                if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                    $imagePath = $uploadDir . $filename;
                }
            } else {
                $errors[] = "Invalid file type. Only JPG, PNG, GIF allowed.";
            }
        }
    }

    if (empty($errors)) {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE Assessment_Visitors SET name=?, email=?, phone=?, purpose=?, status=?, image_path=? WHERE id=?");
            $stmt->execute([$name, $email, $phone, $purpose, $status, $imagePath, $id]);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO Assessment_Visitors (name, email, phone, purpose, status, image_path) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $email, $phone, $purpose, $status, $imagePath]);
        }
        header("Location: dashboard.php");
        exit;
    }
}

include '../includes/header.php';
?>
<form method="POST" enctype="multipart/form-data">
    <h2><?= $id ? 'Edit Visitor' : 'Add New Visitor' ?></h2>
    <input type="hidden" name="csrf" value="<?= generateCsrf() ?>">

    <div class="form-grid">
        <?php if (!empty($errors)): ?>
            <div class="form-group full-width">
                <div class="alert alert-danger" style="background:#f8d7da; color:#721c24; padding:1rem;">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= esc($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" name="name" id="name" value="<?= esc($vis['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="purpose">Purpose of Visit</label>
            <input type="text" name="purpose" id="purpose" value="<?= esc($vis['purpose'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" value="<?= esc($vis['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" name="phone" id="phone" value="<?= esc($vis['phone'] ?? '') ?>">
        </div>

        <?php if ($id): ?>
            <div class="form-group">
                <label for="status">Visit Status</label>
                <select name="status" id="status">
                    <option value="Signed In" <?= ($vis['status'] ?? '') === 'Signed In' ? 'selected' : '' ?>>Signed In
                    </option>
                    <option value="Signed Out" <?= ($vis['status'] ?? '') === 'Signed Out' ? 'selected' : '' ?>>Signed Out
                    </option>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group full-width">
            <label>Visitor Photograph</label>
            <div class="photo-section">
                <?php if ($vis && $vis['image_path']): ?>
                    <div style="margin-bottom: 1rem; text-align: center;">
                        <img src="<?= $vis['image_path'] ?>" class="card-img"
                            style="position: static; margin-bottom: 15px;">
                        <p style="font-size: 0.8rem; color: var(--text-light);">Current photo above. Upload or capture new
                            one to replace.</p>
                    </div>
                <?php endif; ?>

                <div id="uploadOptions">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <input type="file" name="image" accept="image/*" id="fileInput">
                    </div>

                    <div class="or-divider">OR CAPTURE FROM CAMERA</div>

                    <button type="button" class="btn btn-secondary btn-block" onclick="startCamera()">
                        📷 Launch Camera Preview
                    </button>
                </div>

                <div id="cameraModal" class="camera-modal" style="display:none;">
                    <div class="camera-content">
                        <span class="close-modal" onclick="closeCamera()">&times;</span>
                        <h3>Camera Snapshot</h3>
                        <video id="video" autoplay></video>
                        <div class="camera-controls">
                            <button type="button" class="btn btn-primary" onclick="capturePhoto()">Capture
                                Photo</button>
                            <button type="button" class="btn btn-secondary" onclick="closeCamera()">Cancel</button>
                        </div>
                    </div>
                    <canvas id="canvas" style="display:none;"></canvas>
                </div>

                <div id="previewContainer" style="display:none; margin-top:1.5rem;">
                    <img id="photoPreview" src="">
                    <input type="hidden" name="camera_image" id="cameraImageInput">
                    <div style="margin-top: 1rem;">
                        <button type="button" class="btn btn-warning btn-sm" onclick="retakePhoto()">Retake
                            Photo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: right; border-top: 1px solid var(--border); padding-top: 2rem;">
        <button type="submit" class="btn btn-primary" style="min-width: 200px;">
            <?= $id ? 'Save Changes' : 'Complete Sign In' ?>
        </button>
    </div>
</form>

<script>
    let video = document.getElementById('video');
    let canvas = document.getElementById('canvas');
    let photoPreview = document.getElementById('photoPreview');
    let cameraImageInput = document.getElementById('cameraImageInput');
    let cameraModal = document.getElementById('cameraModal');
    let stream = null;

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            cameraModal.style.display = 'flex';
        } catch (err) {
            alert("Error accessing camera: " + err);
        }
    }

    function closeCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        cameraModal.style.display = 'none';
    }

    function capturePhoto() {
        if (!stream) return;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        let dataUrl = canvas.toDataURL('image/png');
        photoPreview.src = dataUrl;
        cameraImageInput.value = dataUrl;
        closeCamera();
        document.getElementById('previewContainer').style.display = 'block';
    }

    function retakePhoto() {
        document.getElementById('previewContainer').style.display = 'none';
        cameraImageInput.value = '';
        startCamera();
    }
</script>
<?php include '../includes/footer.php'; ?>
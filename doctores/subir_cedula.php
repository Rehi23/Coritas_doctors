<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subir Cédula - Corita's Doctor</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
    body {
        font-family: "Poppins", sans-serif;
        background-color: #F2F6FC;
        margin: 0;
        height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Topbar */
    .topbar {
        height: 60px;
        background-color: #1B76D1;
        color: white;
        display: flex;
        align-items: center;
        padding: 0 20px;
        font-weight: 600;
        box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
    }

    /* Centered content */
    .content {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .upload-card {
        background: white;
        padding: 40px;
        border-radius: 12px;
        width: 100%;
        max-width: 520px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.12);
        text-align: center;
    }

    .upload-box {
        border: 2px dashed #1B76D1;
        border-radius: 12px;
        padding: 45px;
        cursor: pointer;
        transition: 0.3s;
    }

    .upload-box:hover {
        background-color: #eaf3ff;
    }

    .uploaded-file {
        background-color: #eaf3ff;
        border: 1px solid #a8c9ff;
        padding: 12px 18px;
        border-radius: 10px;
        font-weight: 500;
        margin-top: 15px;
        display: none;
    }

    .btn-upload {
        background-color: #1B76D1;
        color: white;
        font-weight: 600;
        padding: 12px 25px;
        border-radius: 10px;
        margin-top: 25px;
        border: none;
        width: 100%;
        font-size: 16px;
    }

    .btn-upload:hover {
        background-color: #0a5ca7;
    }

    .note {
        font-size: 14px;
        color: #4d5770;
        margin-top: 10px;
    }
</style>
</head>

<body>

<!-- Topbar -->
<div class="topbar">Subir Cédula Profesional</div>

<!-- Content -->
<div class="content">
    <div class="upload-card">

        <h4 style="color:#1B76D1;font-weight:700;">Verificación de Identidad</h4>
        <p class="text-muted mb-4">Sube tu cédula profesional para continuar</p>

        <label class="upload-box" for="fileInput">
            <i class="bi bi-cloud-upload" style="font-size:45px;color:#1B76D1;"></i>
            <p class="mt-2 mb-0 fs-6">Haz clic o arrastra tu archivo aquí</p>
        </label>

        <input type="file" id="fileInput" style="display:none" accept=".pdf,.jpg,.png">

        <div id="uploadedFile" class="uploaded-file"></div>

        <button class="btn-upload" id="btnSend" disabled>Subir documento</button>

        <p class="note">Formatos permitidos: PDF, JPG, PNG — Máx 5MB</p>
    </div>
</div>

<script>
document.getElementById("fileInput").addEventListener("change", function(){
    let file = this.files[0];
    if(file){
        document.getElementById("uploadedFile").style.display = "block";
        document.getElementById("uploadedFile").textContent = "📎 " + file.name;
        document.getElementById("btnSend").disabled = false;
    }
});

// Optional: simulate redirect after upload
document.getElementById("btnSend").addEventListener("click", () => {
    alert("Documento enviado correctamente ✅");
    window.location.href = "perfil.html";
});
</script>

</body>
</html>


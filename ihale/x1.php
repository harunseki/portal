<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Yeni İhale Ekle</h2>
        </div>
    </div>
</section>
<section class="content">
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $files = $_FILES['files'];
        $fileNames = $_POST['file_names'] ?? [];
        $personels = $_POST['personel'] ?? [];

        foreach ($files['name'] as $i => $name) {
            if ($name && $files['error'][$i] === 0) {
                $tmp = $files['tmp_name'][$i];
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $newFileName = uniqid() . '.' . $ext;
                $uploadDir = "backup/ihaleler/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                move_uploaded_file($tmp, $uploadDir . $newFileName);

                $displayName = trim($fileNames[$i] ?? $name);
                $allowedPersonels = $personels[$i] ?? [];

                // Eğer tek string gelirse array'e çevir
                if (!is_array($allowedPersonels)) {
                    $allowedPersonels = [$allowedPersonels];
                }

                // 1️⃣ ihale_dosyalar tablosuna ekle
                $stmt = $dba->prepare("
                INSERT INTO ihale_dosyalar (baslik, dosya_adi, uploaded_by) 
                VALUES (?, ?, ?)
            ");
                $stmt->bind_param("sss", $displayName, $newFileName , $username);
                $stmt->execute();
                $fileId = $stmt->insert_id;
                $stmt->close();

                // 2️⃣ Yetkili personelleri ekle
                foreach ($allowedPersonels as $p) {
                    $stmt = $dba->prepare("
                    INSERT INTO ihale_dosya_personel (dosya_id, sicil_no) 
                    VALUES (?, ?)
                ");
                    $stmt->bind_param("is", $fileId, $p);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        alert_success("Dosyalar başarıyla yüklendi.");
    }
    ?>
    <form action="#" method="post" enctype="multipart/form-data" id="ihaleForm">
        <div id="fileContainer"></div>

        <button type="button" id="addFileBtn">Yeni Dosya Ekle</button>
        <button type="submit">Dosyaları Yükle</button>
    </form>

    <!-- Chosen CSS + JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        let fileCount = 0;
        const maxInitial = 3; // başlangıçta gösterilecek sayısı
        const fileContainer = $('#fileContainer');

        // Personelleri PHP ile JS değişkenine aktar
        const personeller = <?php
            $personelArr = [];
            $q = $dba->query("SELECT sicil_no, adi, soyadi FROM personeller ORDER BY adi, soyadi");
            while ($row = $dba->fetch_assoc($q)) {
                $personelArr[] = ['sicil_no'=>$row['sicil_no'], 'adi'=>$row['adi']];
            }
            echo json_encode($personelArr);
            ?>;

        function addFileBlock() {
            const idx = fileCount++;
            let options = '';
            personeller.forEach(p => {
                options += `<option value="${p.sicil_no}">${p.adi}</option>`;
            });

            const html = `
        <div class="file-block" style="margin-bottom:20px; border:1px solid #ccc; padding:15px; border-radius:8px;">
            <label>Dosya ${idx+1}:</label>
            <input type="file" name="files[]" class="file-input" >

            <label>Dosya Adı:</label>
            <input type="text" name="file_names[]" placeholder="Dosya Adı" style="width:100%; margin-bottom:5px;" >

            <label>Görme yetkisi (personeller):</label>
            <select name="personel[${idx}][]" class="chosen-select" multiple data-placeholder="Personel Seçiniz" style="width:100%;">
                ${options}
            </select>
        </div>
    `;

            const $block = $(html);
            fileContainer.append($block);

        }

        // Başlangıçta 3 dosya bloğu ekle
        for (let i=0; i<maxInitial; i++) {
            addFileBlock();
        }

        // Yeni dosya ekleme butonu
        $('#addFileBtn').click(function(){
            addFileBlock();
        });
    </script>
</section>
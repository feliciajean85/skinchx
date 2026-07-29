<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php
wp_enqueue_media();
$services = get_all_services(); ?>
<style>
    /* ============================================
       KAWAII COLOR SCHEME
       ============================================ */
    :root {
        --kawaii-pink: #FFB5C5;
        --kawaii-pink-light: #FFE4EC;
        --kawaii-pink-dark: #E8829A;
        --kawaii-mint: #98D9C2;
        --kawaii-mint-light: #E8F5F0;
        --kawaii-text: #5A4A5A;
        --kawaii-text-light: #8A7A8A;
        --kawaii-white: #FFFFFF;
        --kawaii-shadow: rgba(255, 181, 197, 0.3);
        --kawaii-border-radius: 12px;
    }
    
    .kawaii-program-wrap {
        font-family: 'Quicksand', sans-serif;
        background: linear-gradient(135deg, var(--kawaii-pink-light) 0%, var(--kawaii-mint-light) 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .kawaii-program-wrap h1,
    .kawaii-program-wrap h2,
    .kawaii-program-wrap h3 {
        font-family: 'Quicksand', sans-serif;
        color: var(--kawaii-text);
    }
    
    .kawaii-program-wrap .kawaii-card {
        background: var(--kawaii-white);
        border-radius: var(--kawaii-border-radius);
        box-shadow: 0 4px 20px var(--kawaii-shadow);
        padding: 25px;
        margin-bottom: 20px;
        border: 1px solid var(--kawaii-pink-light);
    }
    
    .kawaii-program-wrap .kawaii-card h3 {
        color: var(--kawaii-pink-dark);
        border-bottom: 2px solid var(--kawaii-pink-light);
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    
    .kawaii-program-wrap label {
        color: var(--kawaii-text);
        font-weight: 600;
    }
    
    .kawaii-program-wrap .form-control,
    .kawaii-program-wrap input[type="text"],
    .kawaii-program-wrap select,
    .kawaii-program-wrap textarea {
        border: 2px solid var(--kawaii-pink-light);
        border-radius: 8px;
        padding: 10px 15px;
        transition: all 0.3s ease;
        font-family: 'Quicksand', sans-serif;
    }
    
    .kawaii-program-wrap .form-control:focus,
    .kawaii-program-wrap input[type="text"]:focus,
    .kawaii-program-wrap select:focus,
    .kawaii-program-wrap textarea:focus {
        border-color: var(--kawaii-pink);
        box-shadow: 0 0 0 3px var(--kawaii-shadow);
        outline: none;
    }
    
    .kawaii-program-wrap .btn-primary,
    .kawaii-program-wrap .btn.btn-primary {
        background: linear-gradient(135deg, var(--kawaii-pink) 0%, var(--kawaii-pink-dark) 100%);
        border: none;
        border-radius: 25px;
        padding: 10px 25px;
        font-family: 'Quicksand', sans-serif;
        font-weight: 600;
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px var(--kawaii-shadow);
    }
    
    .kawaii-program-wrap .btn-primary:hover,
    .kawaii-program-wrap .btn.btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--kawaii-shadow);
    }
    
    .kawaii-program-wrap .btn-secondary {
        background: var(--kawaii-mint);
        border: none;
        border-radius: 25px;
        padding: 10px 25px;
        font-family: 'Quicksand', sans-serif;
        font-weight: 600;
        color: var(--kawaii-text);
    }
    
    .kawaii-program-wrap .select2-container--default .select2-selection--single {
        border: 2px solid var(--kawaii-pink-light);
        border-radius: 8px;
        height: 42px;
        padding: 5px;
    }
    
    .kawaii-program-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
    
    /* Preview container - always desktop width */
    .kawaii-preview-container {
        width: 100%;
        overflow-x: auto;
        background: #f9f9f9;
        border-radius: var(--kawaii-border-radius);
        padding: 15px;
        margin-top: 20px;
    }
    
    .kawaii-preview-container .pdfHtml {
        width: 1000px !important;
        min-width: 1000px !important;
        background: white;
    }
    
    /* Scale preview on mobile using zoom (affects layout, no dead space) */
    @media screen and (max-width: 1100px) {
        .kawaii-preview-container .pdfHtml {
            zoom: 0.9;
        }
    }
    
    @media screen and (max-width: 900px) {
        .kawaii-preview-container .pdfHtml {
            zoom: 0.7;
        }
    }
    
    @media screen and (max-width: 700px) {
        .kawaii-preview-container .pdfHtml {
            zoom: 0.5;
        }
    }
    
    @media screen and (max-width: 500px) {
        .kawaii-preview-container .pdfHtml {
            zoom: 0.35;
        }
    }

    /* Set the dimensions of the chart */
    .chartContainer {
        width: 350px;
        /* Set your desired width */
        height: 250px;
        /* Set your desired height */
        margin: auto;
        /* Center the chart */
    }

    canvas {
        display: block;
        width: 100% !important;
        /* Ensure the canvas fits the container */
        height: 100% !important;
        /* Ensure the canvas fits the container */
    }

    .chartTitle {
        min-height: 25px;
        font-size: 20px;
        line-height: 25px;
        margin-bottom: 8px;
    }

    .secondSectonTextCol strong,
    .secondSectonTextCol label,
    .secondSectonTextCol span {
        /* font-size: 20px !important; */
    }

    .secondSectonTextCol .mb-2 {
        line-height: 24px;
    }

    .secondSectonTextCol .mb-1 {
        line-height: 20px;
    }

    #ui-datepicker-div {
        z-index: 99 !important;
    }

    .secondSectonTextCol .sectionTitle {
        font-size: 25px;
        line-height: 30px;
        font-weight: 700;
        margin-bottom: 29px;
        padding-top: 0px !important;
        margin-top: 0;
    }

    .formInput textarea,
    .formInput input {
        margin-top: 10px;
    }

    #chat_gpt_sumarry {
        min-height: 50px;
        resize: none;
        box-sizing: border-box;
        /* overflow: hidden; */
        /* overflow-y: auto; */
        /* word-break: break-all; */
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        position: relative;
    }

    .modal-content h2 {
        margin: 0 0 20px;
    }

    .modal-content input {
        width: calc(100% - 22px);
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .modal-content button {
        /* padding: 10px 20px;
      margin: 10px 5px 0;
      cursor: pointer;
      border: none;
      border-radius: 4px;
      background: #007bff;
      color: white; */
    }

    .modal-close-icon {
        position: absolute;
        top: 10px;
        right: 10px;
        cursor: pointer;
        font-size: 18px;
        background: none;
        border: none;
        color: #333;
    }

    .email-list {
        margin-top: 10px;
        list-style: none;
        padding: 0;
    }

    .email-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9f9f9;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 5px;
    }

    .email-list .remove-icon {
        cursor: pointer;
        border: none;
        margin: 0;
        font-size: 18px;
        line-height: 20px;
        height: 20px;
        width: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0;
        background: red;
        border-radius: 50%;
        color: #fff;
    }

    .modal-content #closeModalBtn {
        margin: 0;
        border-radius: 50%;
        height: 35px;
        width: 35px;
        font-size: 24px;
        line-height: 24px;
        padding: 0;
        background-color: red;
        color: #fff;
    }

    /*    //program*/
    @import url("https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100..900;1,100..900&display=swap");

    :root {
        --primary-color: #5f9387;
    }

    .bg_footer {
        background-color: #61968c;
    }

    .pdfHtml * {
        margin-top: 0;
        font-family: "Jost", serif;
    }

    .pdfHtml section,
    .pdfHtml div {
        /* line-height: 0; */
    }

    .container {
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
    }

    img {
        max-width: 100%;
    }

    .pdfHtml table,
    .w-100 {
        width: 100%;
    }

    .firstPdfDyText {
        position: absolute;
        max-width: 350px;
        width: 100%;
        bottom: 15%;
        left: 12%;
    }

    .firstPdfDyText .imgCol img {
        min-width: 70px;
        max-width: 70px;
    }

    .firstPdfDyText p {
        margin-bottom: 0;
    }

    .firstPdfDyText * {
        font-size: 20px;
    }

    .generating .firstPdfDyText * {
        font-size: 20px !important;
    }

    .section4DyText {
        padding: 25px;
        border-radius: 30px;
        position: absolute;
        max-width: 36%;
        background-color: var(--primary-color);
        width: 100%;
        bottom: 18%;
        right: 5%;
    }

    .section4DyText * {
        color: #fff;
    }

    .section4DyText * {
        font-size: 25px;
        line-height: 30px;
    }

    .section4DyText h2 {
        font-size: 34px;
        line-height: 40px;
        font-weight: 700;
        margin-bottom: 30px;
    }

    .section4DyText ul {
        padding: 0;
    }

    .section4DyText ul li {
        list-style: none;
        margin-bottom: 25px;
        background-image: url("./images/pointerIconLogo1.png");
        background-repeat: no-repeat;
        background-size: 20px;
        padding-left: 40px;
        background-position: 0 5px;
    }

    .footerArea p {
        margin: 0;
        margin-top: 7px;
        font-size: 24px;
        text-align: center;
    }

    .qrCodeArea {
        min-width: 105px;
    }

    img.service_logo {
        width: 320px;
        height: 55px;
    }

    img.service_logo {
        width: auto;
        height: 55px;
    }

    img.qr_img {
        width: 105px;
        height: 105px;
        object-fit: contain;
    }

    .footerArea .selected_field_date p {
        margin-bottom: 0px;
        margin-top: 0px;

    }

    /* program */
    @media only screen and (max-width: 1100px) {
        .firstPdfDyText {
            max-width: 300px;
            bottom: 9%;
        }

        .firstPdfDyText .item {
            margin-bottom: 15px !important;
        }

        .firstPdfDyText .imgCol img {
            min-width: 45px;
            max-width: 45px;
        }

        .firstPdfDyText .imgCol {
            margin-right: 10px !important;
        }

        .firstPdfDyText * {
            font-size: 19px;
        }
    }

    @media only screen and (max-width: 767px) {
        .firstPdfDyText {
            max-width: 250px;
        }

        .firstPdfDyText * {
            font-size: 15px;
        }

        .firstPdfDyText .imgCol img {
            min-width: 35px;
            max-width: 35px;
        }

        .firstPdfDyText .imgCol {
            margin-right: 7px !important;
        }

        .footerArea .leftMain {
            padding: 7px !important;
            padding-right: 0 !important;
        }

        img.qr_img {
            width: 75px;
            height: 75px;
        }

        .qrCodeArea {
            min-width: 75px;
        }

        .parentColw {
            padding: 7px !important;
        }

        .footerArea p {
            font-size: 1rem;
        }
    }

    @media only screen and (max-width: 600px) {
        .firstPdfDyText {
            max-width: 215px;
        }

        .firstPdfDyText * {
            font-size: 0.85rem;
        }
    }

    @media only screen and (max-width: 520px) {
        .firstPdfDyText {
            max-width: 210px;
        }

        .firstPdfDyText * {
            font-size: 0.8rem;
        }

        img.qr_img {
            width: 45px;
            height: 45px;
        }

        .qrCodeArea {
            min-width: 45px;
        }

        img.service_logo {
            height: 25px;
            object-fit: contain;
        }

        .footerArea p {
            font-size: 0.8rem;
        }
    }

    @media only screen and (max-width: 500px) {
        .firstPdfDyText {
            max-width: 150px;
        }

        .firstPdfDyText * {
            font-size: 0.55rem;
        }
    }

    @media only screen and (max-width: 375px) {
        .firstPdfDyText .imgCol img {
            min-width: 27px;
            max-width: 27px;
        }

        .firstPdfDyText {
            max-width: 125px;
        }

        .firstPdfDyText * {
            font-size: 0.43rem;
        }

        .footerArea p {
            font-size: 0.5rem;
        }
    }

    /* generating css */
    .generating .firstPdfDyText {
        max-width: 350px !important;
        bottom: 15% !important;
    }

    .generating .firstPdfDyText .imgCol img {
        min-width: 70px !important;
        max-width: 70px !important;
    }

    .generating .firstPdfDyText .item {
        margin-bottom: 3rem !important;
    }

    .generating .firstPdfDyText .imgCol {
        margin-right: 1.5rem !important;
    }

    .generating .footerArea .leftMain {
        padding: 3rem !important;
        padding-right: 3rem !important;
    }

    .generating img.qr_img {
        width: 105px !important;
        height: 105px !important;
    }

    .generating .qrCodeArea {
        min-width: 105px !important;
    }

    .generating .footerArea .parentColw {
        padding: 1rem !important;
    }

    .generating img.service_logo {
        height: 55px !important;
    }

    .generating .footerArea p {
        font-size: 24px !important;
    }

    /* generating css */
    
    /* ============================================
       PDF GENERATION - Force Desktop Sizing
       ============================================ */
    .generating,
    .generating .pdfHtml,
    .generating .pdf-capture,
    .generating .pdf-capture .container {
        width: 1200px !important;
        max-width: 1200px !important;
        min-width: 1200px !important;
        margin: 0 auto !important;
        overflow: visible !important;
    }
    
    /* Override preview container specificity during PDF generation */
    .kawaii-preview-container .pdfHtml.generating {
        width: 1200px !important;
        max-width: 1200px !important;
        min-width: 1200px !important;
        zoom: 1 !important;
        transform: none !important;
    }
    
    .generating img {
        max-width: 100% !important;
    }
    
    .generating table {
        width: 100% !important;
        display: table !important;
    }
    
    .generating .amlia_form_btns,
    .generating .sticky-bottom {
        display: none !important;
    }
    
    /* Mobile Responsive Fixes */
    @media screen and (max-width: 782px) {
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
        }
        
        .kawaii-program-wrap {
            padding: 10px;
        }
        
        .kawaii-card {
            padding: 15px;
        }
        
        /* Preview container scrolls horizontally on mobile */
        .kawaii-preview-container {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            padding: 10px;
        }
        
        .amlia_form_btns {
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
            padding: 10px !important;
            width: 100% !important;
            box-sizing: border-box !important;
            align-items: center !important;
        }
        
        .amlia_form_btns button,
        .amlia_form_btns .btn {
            width: 75% !important;
            margin: 0 auto !important;
            box-sizing: border-box !important;
            display: block !important;
        }
        
        .sticky-bottom {
            position: relative !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
        .modal-content {
            width: 90% !important;
            max-width: 90% !important;
        }
        
        /* Reset ALL mobile styles during PDF generation */
        .kawaii-preview-container .pdfHtml.generating {
            zoom: 1 !important;
            transform: none !important;
            width: 1200px !important;
            min-width: 1200px !important;
            max-width: 1200px !important;
        }
        
        .generating .firstPdfDyText {
            max-width: 350px !important;
            bottom: 15% !important;
        }
        
        .generating .firstPdfDyText * {
            font-size: 20px !important;
        }
        
        .generating .firstPdfDyText .imgCol img {
            min-width: 70px !important;
            max-width: 70px !important;
        }
        
        .generating .firstPdfDyText .imgCol {
            margin-right: 1.5rem !important;
        }
        
        .generating .firstPdfDyText .item {
            margin-bottom: 3rem !important;
        }
        
        .generating .footerArea .leftMain {
            padding: 3rem !important;
            padding-right: 3rem !important;
        }
        
        .generating img.qr_img {
            width: 105px !important;
            height: 105px !important;
        }
        
        .generating .qrCodeArea {
            min-width: 105px !important;
        }
        
        .generating img.service_logo {
            height: 55px !important;
        }
        
        .generating .footerArea p {
            font-size: 24px !important;
        }
        
        .generating .footerArea .parentColw {
            padding: 1rem !important;
        }
        
        .generating .container {
            max-width: 1200px !important;
        }
        
        .generating img {
            max-width: 100% !important;
            height: auto !important;
        }
    }
</style>


<?php
$referer_url = (wp_get_referer()) ? wp_get_referer() : admin_url('admin.php?page=amelia-report');
$wp_date_format = get_option('date_format'); // Example: 'd/m/Y'
$jq_date_format = str_replace(
    array('d', 'j', 'm', 'n', 'Y', 'y'),
    array('dd', 'd', 'mm', 'm', 'yy', 'y'),
    $wp_date_format
);
?>
<div class="kawaii-program-wrap">
    <h1 style="margin-bottom: 20px; font-size: 28px;">
        <span style="display: inline-block; width: 12px; height: 12px; background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%); border-radius: 50%; margin-right: 10px;"></span>
        Program Materials
    </h1>
    
<form method="post" id="meterial_form">
    <input type="hidden" name="report_email" value="" id="report_to_email">
    
    <div class="kawaii-card">
        <h3>Select Service</h3>
        <div class="d-flexs">
            <div>
                <label for="serviceFilter">Service:</label>
                <select id="serviceFilter" class="select2drop" name="service_id">
                    <option value="">Select Service</option>
                    <?php if (!empty($services)) {
                        foreach ($services as $serv) { ?>
                            <option value="<?php echo $serv->id; ?>"><?php echo $serv->name; ?></option>
                    <?php }
                    } ?>
                </select>
            </div>
        </div>
    </div>
    
    <div class="kawaii-card" id="form_metarials_field">
        <h3>Program Materials Details</h3>

        <!-- Date Picker -->
        <div class="mb-3">
            <label for="datePicker" class="form-label fw-bold">Line 1 Text:</label>
            <input type="text" name="selected_dates" class="form-control limit-field" id="service_dates" placeholder="Dates" data-next="service_dates_2">
            <p>
                Limit: <small class="text-muted" id="count_service_dates">0 / 25</small> characters (with spaces).
            </p>

        </div>
        <div class="mb-3">
            <label for="datePicker" class="form-label fw-bold">Line 2 Text:</label>
            <input type="text" name="selected_dates_2" class="form-control limit-field" id="service_dates_2" placeholder="Dates" data-next="service_dates_3">
            <p>
                Limit: <small class="text-muted" id="count_service_dates_2">0 / 25</small> characters (with spaces).
            </p>

        </div>
        <div class="mb-3">
            <label for="datePicker" class="form-label fw-bold">Line 3 Text:</label>
            <input type="text" name="selected_dates_3" class="form-control limit-field" id="service_dates_3" placeholder="Dates">
            <p>
                Limit: <small class="text-muted" id="count_service_dates_3">0 / 25</small> characters (with spaces).
            </p>

        </div>
        <!-- Logo Uploader -->
        <div class="mb-3">
            <label for="logoUploader" class="form-label fw-bold">Upload Logo:</label>
            <div class="input-group">
                <input type="hidden" id="logoUploader" name="logo_url">
                <button type="button" id="uploadLogoBtn" class="btn btn-primary">Upload Logo</button>
            </div>
            <div id="logoPreview" class="mt-2"></div>
        </div>

        <!-- QR Code Uploader -->
        <div class="mb-3">
            <label for="qrUploader" class="form-label fw-bold">Upload QR Code:</label>
            <div class="input-group">
                <input type="hidden" id="qrUploader" name="qr_code_url">
                <button type="button" id="uploadQrBtn" class="btn btn-primary">Upload QR Code</button>
            </div>
            <div id="qrPreview" class="mt-2"></div>
        </div>

    </div>
    
    <div class="kawaii-card">
        <h3>Preview</h3>
        <p style="color: var(--kawaii-text-light); font-size: 14px; margin-bottom: 15px;">Preview shows desktop layout. Scroll horizontally on mobile to view full preview.</p>
        <div class="kawaii-preview-container">
    <main class="pdfHtml" id="content-to-pdf">

        <section class="section1" style="margin-top:20px;">


            <div class="container">


            </div>
        </section>

        <section class="section1 pdf-capture">
            <div class="container position-relative">
                <img
                    class="w-100"
                    src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/Corporate-merged_page-1-1.png"
                    alt="" />
                <div class="firstPdfDyText">
                    <div class="item d-flex mb-5">
                        <div class="imgCol me-4">
                            <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/firstDyIcon1.png" alt="" />
                        </div>
                        <p>
                            Our session times are <strong>20 minutes</strong> each with a
                            maximum of
                            <strong>24 skin checks per day</strong>
                        </p>
                    </div>
                    <div class="item d-flex mb-5">
                        <div class="imgCol me-4">
                            <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/firstDyIcon2.png" alt="" />
                        </div>
                        <p>
                            Our Day schedule options are;
                            <strong>6am-3pm or 8am-5pm</strong>
                        </p>
                    </div>
                    <div class="item d-flex mb-5">
                        <div class="imgCol me-4">
                            <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/firstDyIcon3.png" alt="" />
                        </div>
                        <p>
                            <strong>Our fixed daily rate is <span id="daily_rate" contenteditable="false">$1500+GST</span> <span id="edit_icon" class="removefrompdf" style="cursor:pointer; color:#0073aa; margin-left:8px;">✏️</span>.</strong>
                            Additional fees and charges may apply for flights, car hire and
                            accommodation.
                        </p>
                    </div>
                </div>
            </div>
        </section>
        <section class="section2 pdf-capture">
            <div class="container">
                <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/Corporate-merged_page-2.png" alt="" />
            </div>
        </section>
        <section class="section3 pdf-capture">
            <div class="container">
                <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/Corporate-merged_page-3.png" alt="" />
            </div>
        </section>
        <section class="section4 pdf-capture">
            <div class="container">
                <img
                    class="w-100"
                    src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/Corporate-merged_page-4-11.png"
                    alt="" />

                <div class="footerArea bg_footer">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <div class="p-5 leftMain">
                                <div class="parentColw bg-white d-flex p-3 align-items-center">
                                    <div class="qrCodeArea me-2">
                                        <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/qrCode.png" alt="" class="qr_img" />
                                    </div>
                                    <div>
                                        <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/f-logo.png" alt="" class="service_logo" />
                                        <div class="selected_field_date"></div>
                                        <!--                      <p class="selected_dates" style="text-align:left;line-height:26px;font-size:18px;">--</p>-->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer-1.png" alt="" class="" />
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="section5 pdf-capture">
            <div class="container">
                <img
                    class="w-100"
                    src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/Corporate-merged_page-5-1.png"
                    alt="" />
                <div class="footerArea bg_footer">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <div class="p-5 leftMain">
                                <div class="parentColw bg-white d-flex p-3 align-items-center">
                                    <div class="qrCodeArea me-2">
                                        <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/qrCode.png" alt="" class="qr_img" />
                                    </div>
                                    <div>
                                        <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/f-logo.png" alt="" class="service_logo" />
                                        <div class="selected_field_date"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer-1.png" alt="" />
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="section6 pdf-capture">
            <div class="container">
                <img
                    class="w-100"
                    src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/Corporate-merged_page-6-1.png"
                    alt="" />
                <div class="footerArea bg_footer">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <div class="p-5 leftMain">
                                <div class="parentColw bg-white d-flex p-3 align-items-center">
                                    <div class="qrCodeArea me-2">
                                        <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/qrCode.png" alt="" class="qr_img" />
                                    </div>
                                    <div>
                                        <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/f-logo.png" alt="" class="service_logo" />
                                        <div class="selected_field_date"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer-1.png" alt="" />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
        </div><!-- end kawaii-preview-container -->
    </div><!-- end kawaii-card -->
    
    <div class="kawaii-card" style="background: linear-gradient(135deg, #FFE4EC 0%, #E8F5F0 100%);">
        <div class="amlia_form_btns" style="position: relative; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
            <button class="btn btn-primary" name="submit" type="submit" id="save_program_meterials">Save</button>
            <button class="btn btn-primary" type="button" id="pdf_generator">Pdf Generate</button>
            <button class="btn btn-primary" id="openModalBtn" type="button">Send Report</button>
        </div>
    </div>

    <div id="alert-message" style="display: none; padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; margin-top: 10px;text-align: center; border-radius: 8px;">
        <span id="notification-message"></span>
    </div>
    <input type="hidden" name="daily_rate" id="daily_rate_input" value="$1500+GST">
</form>
</div><!-- end kawaii-program-wrap -->
<div id="emailModal" class="modal">
    <div class="modal-content">
        <button type="button" class="btn-close modal-close-icon" id="closeModalBtn" data-bs-dismiss="modal" aria-label="Close">&times;</button>
        <h2>Add Email Addresses</h2>
        <input class="w-100" type="email" id="emailInput" placeholder="Enter email" />
        <button class="w-100 mx-0 btn-success btn" id="addEmailBtn">Add Email</button>

        <ul class="email-list w-100" id="emailList">

        </ul>
        <button class="btn btn-primary" type="button" id="save_send_program_email">Send Report</button>
    </div>
</div>
<script>
    var ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
    let selectedDates = []; // Array to store selected dates
    let wpDateFormat2 = "<?php echo $jq_date_format; ?>"; // Get WordPress date format
    document.addEventListener("DOMContentLoaded", function() {
        let dailyRate = document.getElementById("daily_rate");
        let hiddenInput = document.getElementById("daily_rate_input");

        // Update hidden input only when editing is finished (blur)
        dailyRate.addEventListener("blur", function() {
            hiddenInput.value = dailyRate.textContent.trim();
        });
    });
    jQuery(document).ready(function($) {
        $("#edit_icon").on("click", function() {
            $("#daily_rate").attr("contenteditable", "true").focus();

        });
        $('.select2drop').select2();
        //            $("#service_dates").on("keyup", function(){
        //                var dates=jQuery(this).val();
        //                $(".selected_dates").text(dates);
        //            });

        $('#serviceFilter').on('change', function() {

            serviceChange();
        });

        function serviceChange() {
            jQuery('#ajax-loader').show();
            const service = $('#serviceFilter option:selected').val();



            $.ajax({
                url: ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_service_meterials',

                    service: service,

                },
                success: function(response) {
                    jQuery('#ajax-loader').hide();
                    var dateshtml = '';

                    if (response.selected_dates) {
                        $("#service_dates").val(response.selected_dates);
                        dateshtml += '<p class="selected_dates1" style="text-align:left;line-height:26px;font-size:18px;">' + response.selected_dates + '</p>';
                        //$('.selected_dates').text(response.selected_dates);
                    }
                    if (response.selected_dates_2) {
                        $("#service_dates_2").val(response.selected_dates_2);
                        dateshtml += '<p class="selected_dates2" style="text-align:left;line-height:26px;font-size:18px;">' + response.selected_dates_2 + '</p>';
                        //$('.selected_dates').text(response.selected_dates);
                    }
                    if (response.selected_dates_3) {
                        $("#service_dates_3").val(response.selected_dates_3);
                        dateshtml += '<p class="selected_dates3" style="text-align:left;line-height:26px;font-size:18px;">' + response.selected_dates_3 + '</p>';
                        //$('.selected_dates').text(response.selected_dates);
                    }
                    $('.selected_field_date').html(dateshtml);
                    if (response.daily_rate) {
                        $("#daily_rate").text(response.daily_rate);
                        $("#daily_rate_input").val(response.daily_rate);
                    }
                    if (response.selected_dates) {
                        //let dateArray = response.selected_dates.split(", "); // Convert to array
                        //console.log(dateArray);
                        //selectedDates = dateArray;
                        // $("#datePicker").datepicker("refresh");
                        //  $("#service_dates").val(response.selected_dates);
                    }
                    if (response.logo_url) {
                        $("#logoUploader").val(response.logo_url);
                        $('.service_logo').attr("src", response.logo_url);
                        $("#logoPreview").html('<img src="' + response.logo_url + '" class="img-fluid rounded border mt-2" style="max-width: 150px;">');
                    } else {
                        $("#logoUploader").val('');
                        $('.service_logo').attr("src", '');
                        $("#logoPreview").html('');
                    }
                    if (response.qr_code_url) {
                        $("#qrUploader").val(response.qr_code_url);
                        $('.qr_img').attr("src", response.qr_code_url);
                        $("#qrPreview").html('<img src="' + response.qr_code_url + '" class="img-fluid rounded border mt-2" style="max-width: 150px;">');
                    } else {
                        $("#qrUploader").val('https://placehold.co/300x300');
                        $('.qr_img').attr("src", 'https://placehold.co/300x300');
                        $("#qrPreview").html('<img src="https://placehold.co/300x300" class="img-fluid rounded border mt-2" style="max-width: 150px;">');
                    }
                    field_value_counter();
                }
            });
        }
        // Date Picker with WordPress Core Date Format


        function formatDate(date) {
            return $.datepicker.formatDate(wpDateFormat2, date);
        }

        $("#datePicker").datepicker({
            dateFormat: wpDateFormat2,
            beforeShowDay: function(date) {
                let formattedDate = formatDate(date);
                return [true, selectedDates.includes(formattedDate) ? "ui-state-highlight" : ""];
            },
            onSelect: function(dateText) {
                if (selectedDates.includes(dateText)) {
                    selectedDates = selectedDates.filter(d => d !== dateText); // Remove if already selected
                } else {
                    selectedDates.push(dateText); // Add new date
                }
                $("#datePicker").val(selectedDates.join(", ")); // Display selected dates
                $(".selected_dates").text(selectedDates.join(", "));
                // class="service_logo"
            }
        });

        function openMediaUploader(buttonId, inputFieldId, previewClass, maxWidth, maxHeight) {
            let frame = wp.media({
                title: "Select Image",
                multiple: false,
                library: {
                    type: "image"
                },
                button: {
                    text: "Use this Image"
                }
            });

            frame.on("select", function() {
                let attachment = frame.state().get("selection").first().toJSON();
                var response = attachment.url;
                $("#" + inputFieldId).val(response);
                $('.' + previewClass + '').attr("src", response);
                $("#" + buttonId).html('<img src="' + response + '" class="img-fluid rounded border mt-2" style="max-width: 150px;">');
                // Send AJAX request to resize image
                /* $.ajax({
                     url: "<?php //echo admin_url('admin-ajax.php'); 
                            ?>",
                     type: "POST",
                     data: {
                         action: "resize_uploaded_image",
                         image_url: attachment.url,
                         max_width: maxWidth,
                         max_height: maxHeight
                     },
                     success: function(response) {
                         $("#" + inputFieldId).val(response);
                         $('.'+previewClass+'').attr("src", response);
                         $("#" + buttonId).html('<img src="' + response + '" class="img-fluid rounded border mt-2" style="max-width: 150px;">');
                     }
                 });*/
            });

            frame.open();
        }


        // WordPress Media Uploader for Logo
        $("#uploadLogoBtn").click(function(e) {
            e.preventDefault();
            openMediaUploader("logoPreview", "logoUploader", "service_logo", 350, 50);
            /* e.preventDefault();
             let frame = wp.media({
                 title: "Select Logo",
                 multiple: false,
                 library: { type: "image" },
                 button: { text: "Use this logo" }
             });

             frame.on("select", function() {
                 let attachment = frame.state().get("selection").first().toJSON();
                 $("#logoUploader").val(attachment.url);
                 $("#logoPreview").html('<img src="' + attachment.url + '" class="img-fluid rounded border mt-2" style="max-width: 150px;">');
                // service_logo
             });

             frame.open();*/
        });

        // WordPress Media Uploader for QR Code
        $("#uploadQrBtn").click(function(e) {

            e.preventDefault();
            openMediaUploader("qrPreview", "qrUploader", "qr_img", 350, 50);
            /*let frame = wp.media({
                title: "Select QR Code",
                multiple: false,
                library: { type: "image" },
                button: { text: "Use this QR Code" }
            });

            frame.on("select", function() {
                let attachment = frame.state().get("selection").first().toJSON();
                $("#qrUploader").val(attachment.url);
                $("#qrPreview").html('<img src="' + attachment.url + '" class="img-fluid rounded border mt-2" style="max-width: 150px;">');
            });

            frame.open();*/
        });
    });
</script>

<style>
    /* Professional Form Styling */
    #customForm {
        max-width: 500px;
        margin: auto;
    }

    #customForm h3 {
        color: #333;
    }

    .form-label {
        color: #555;
    }
</style>
<script>
    let emails = [];
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const emailModal = document.getElementById('emailModal');
    const addEmailBtn = document.getElementById('addEmailBtn');
    const emailInput = document.getElementById('emailInput');
    const emailList = document.getElementById('emailList');
    const emailField = document.getElementById('report_to_email');
    //const emailField = document.getElementById('remove-icon');



    // Show the modal

    jQuery(document).on('click', '.remove-icon', function() {
        jQuery(this).closest('li').remove();
        var semail = jQuery(this).data('email');
        emails = emails.filter(e => e !== semail);
        updateHiddenField();
    });
    openModalBtn.addEventListener('click', () => {
        emailModal.style.display = 'flex';
    });

    // Close the modal
    closeModalBtn.addEventListener('click', () => {
        emailModal.style.display = 'none';
    });

    // Add email to the list and update hidden field
    addEmailBtn.addEventListener('click', () => {
        const email = emailInput.value.trim();
        if (email) {
            if (emails.includes(email)) {
                alert('Email already added.');
                return;
            }
            emails.push(email);

            const listItem = document.createElement('li');
            listItem.textContent = email;

            // Add a remove button for each email
            const removeBtn = document.createElement('button');
            removeBtn.innerHTML = '&times;';
            removeBtn.className = 'remove-icon';
            removeBtn.title = 'Remove Email';
            removeBtn.addEventListener('click', () => {
                emails = emails.filter(e => e !== email);
                updateHiddenField();
                emailList.removeChild(listItem);
            });

            listItem.appendChild(removeBtn);
            emailList.appendChild(listItem);

            updateHiddenField();
            emailInput.value = ''; // Clear input
        } else {
            alert('Please enter a valid email address.');
        }
    });

    // Update the hidden input field with comma-separated emails
    function updateHiddenField() {
        emailField.value = emails.join(',');
    }

    // Close modal when clicking outside the content
    window.addEventListener('click', (e) => {
        if (e.target === emailModal) {
            emailModal.style.display = 'none';
        }
    });
    const maxChars = 25;
    // Handle form submission
    /* document.getElementById('emailForm').addEventListener('submit', (e) => {
       e.preventDefault(); // Prevent actual submission for demonstration
       alert(`Submitted Emails: ${emailField.value}`);
     });*/
    function countWords(str) {
        const words = str.trim().split(/\s+/);
        return words.length === 1 && words[0] === "" ? 0 : words.length;
    }

    // Function to handle word counting for each field
    function field_value_counter() {
        document.querySelectorAll(".limit-field").forEach(input => {

            const countDisplay = document.getElementById("count_" + input.id);
            //alert(wordCount);alert("count_" + input.id);
            if (countDisplay) {
                countDisplay.textContent = input.value.length + " / " + maxChars;
            }
        });
    }
    document.addEventListener("DOMContentLoaded", function() {

        //const container = document.getElementById("selected_field_date");


        // Function to check fields and update the content
        function updateFieldDates() {
            let dateshtml = '';

            // Check each field and create <p> tags if they have values
            const fields = [{
                    id: 'service_dates'
                },
                {
                    id: 'service_dates_2'
                },
                {
                    id: 'service_dates_3'
                }
            ];

            fields.forEach(field => {
                const value = document.getElementById(field.id).value.trim();
                if (value) {
                    dateshtml += `<p class="" style="text-align:left;line-height:26px;font-size:18px;">${value}</p>`;
                }
            });

            jQuery('.selected_field_date').html(dateshtml);
            // Replace the content of the #selected_field_date div with the generated <p> tags
            //container.innerHTML = dateshtml;
        }
        document.querySelectorAll(".limit-field").forEach(input => {
            input.addEventListener("input", function() {

                // Enforce character limit
                if (this.value.length > maxChars) {
                    this.value = this.value.substring(0, maxChars);
                    const nextFieldID = this.getAttribute("data-next");
                    if (nextFieldID) document.getElementById(nextFieldID).focus();
                }

                // Update counter
                const countDisplay = document.getElementById("count_" + this.id);
                if (countDisplay) {
                    countDisplay.textContent = this.value.length + " / " + maxChars;
                }

                // Handle the <p> output
                updateFieldDates();
            });
        });
    });
</script>
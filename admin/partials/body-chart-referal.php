<style>
     .select2.select2-container{
        width:100%!important;
    }
  .pdfHtml table,
  .w-100 {
    width: 100%;
  }
  #refform .borderBottomDotted {
    border-bottom: 1px dotted #000;
  }

  /* New Css */
  #refform .pdfInput {
    padding: 0 10px;
    width: 100%;
    border: none;
    border-bottom: 1px dotted #000;
    line-height: 0;
    min-height: unset;
    line-height: 29px;
    height: 29px;
    border-radius: 0;
  }
  #refform .pdfInput:focus {
    outline: none;
    border: none;
    border-bottom: 1px solid #000;
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
  .restBtn {
    display: flex;
    justify-content: end;
    align-items: end;
    margin-left: 15px;
  }
  
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
  
  .generating .gauge {
    width: 450px !important;
    height: 250px !important;
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
  
  /* Keep signature image constrained during PDF generation */
  .generating #signatureimg img {
    width: 300px !important;
    max-width: 300px !important;
    height: auto !important;
  }
  .generating #signatureimgsec {
    text-align: left !important;
  }
  
  /* Hide empty image slots during PDF generation, center the ones with images */
  .generating .ref-image-empty {
    display: none !important;
  }
  .generating #file_preview_ref {
    justify-content: center !important;
  }
  
  /* Mobile Responsive Fixes */
  @media screen and (max-width: 782px) {
    html, body {
      overflow-x: hidden !important;
      max-width: 100vw !important;
    }
    
    /* Only apply when NOT generating PDF */
    .pdfHtml:not(.generating) .gauge {
      width: 280px !important;
      height: 150px !important;
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
    
    main, .pdfHtml:not(.generating), .pdf-capture:not(.generating .pdf-capture), .container {
      max-width: 100% !important;
      overflow-x: hidden !important;
    }
    
    img {
      max-width: 100% !important;
      height: auto !important;
    }
    
    .modal-content {
      width: 90% !important;
      max-width: 90% !important;
    }
    
    .restBtn {
      margin-left: 0 !important;
      justify-content: center !important;
    }
  }
</style>

<?php
  $id=isset($_GET['id'])?$_GET['id']:'';
    if($id){
    $infoappointment=get_appoinment_booking_details($id);
    //print_r($infoappointment);
    $info=isset($infoappointment['info'])?json_decode($infoappointment['info'],true):array();
    $dob='';
    $lname=isset($info['lastName'])?$info['lastName']:$infoappointment['lastName'];
    $phone=isset($info['phone'])?$info['phone']:$infoappointment['phone'];
    $email=isset($infoappointment['email'])?$infoappointment['email']:'';
    $gender=isset($infoappointment['gender'])?$infoappointment['gender']:'';
    $email=isset($infoappointment['email'])?$infoappointment['email']:'';
    $serviceid=isset($infoappointment['serviceId'])?$infoappointment['serviceId']:'';
    $question_answer=isset($infoappointment['question_answer'])?json_decode($infoappointment['question_answer'],true):array();
       //print_r($question_answer) ;
      $appointment_date=isset($question_answer[2]['value'])?$question_answer[2]['value']:''; 
        $age=isset($question_answer[5]['value'])?$question_answer[5]['value']:'';
        
    $serviceinfo=get_appoinment_service_details( $serviceid);
    $referer_url = admin_url('admin.php?page=wpamelia-bodychart&id=' . $id);
    $body_chart=get_appoinment_body_chart($id);
     $body_chart_ref=get_appoinment_body_chart_ref($id);
      $body_chart_data= json_decode($body_chart->data,true);
        
        if(isset($body_chart_data['name']) && $body_chart_data['name']){
            $fname=$body_chart_data['name'];
        }
        else{
            $fname=isset($info['firstName'])?$info['firstName']:$infoappointment['firstName'];
        }
         if(isset($body_chart_data['email']) && $body_chart_data['email']){
            $email=$body_chart_data['email'];
        }
       
       $body_chart_ref_data= json_decode($body_chart_ref->data,true); 
        if(isset($body_chart_ref_data['name']) && $body_chart_ref_data['name']){
            $fname=$body_chart_ref_data['name'];
        }
          if(isset($body_chart_ref_data['age']) && $body_chart_ref_data['age']){
            $age=$body_chart_ref_data['age'];
        }
        else{
            $age=$body_chart_data['age'];
        }
          if(isset($body_chart_ref_data['phone'])){
            $phone=$body_chart_ref_data['phone'];
        }
          if(isset($body_chart_ref_data['date_screen_care'])){
            $appointment_date=$body_chart_ref_data['date_screen_care'];
        }
        
    ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
 <main class="pdfHtml" id="refform">
      <input name="frontCanvas" type="hidden" id="frontCanvas_point" value="<?php if(isset($body_chart_data['frontCanvas'])) echo $body_chart_data['frontCanvas']; else echo '';?>"> 
      <input name="mark_positions" type="hidden" id="markPositions" value="<?php if(isset($body_chart_data['mark_positions'])) echo htmlspecialchars($body_chart_data['mark_positions'], ENT_QUOTES, 'UTF-8'); else  echo '';?>">
     <input type="hidden" id="refpage" value="1">
      <div class="">
          <br>
           <a class="btn btn-primary" href="<?php echo $referer_url;?>">Go Back</a>
      <section class="section1 pdf-capture">
        <div class="container">
          <div class="headingCol mb-3 pt-4">
            <h1 class="text-center">
              RECOMMENDATION FOR <br />
              SKIN CANCER SCREENING FOLLOW-UP
            </h1>
            <hr class="border-bottom" />
          </div>
          <form action="" id="body_chart_ref_form">
              <input type="hidden" name="report_to_email" id="report_to_email" value="<?php echo $email;?>">
            <div class="mb-2">
              <strong>
                Details of participant who underwent skin screening:
              </strong>
            </div>
            <div class="mb-2 d-flex align-items-end">
              <p class="mb-0 text-nowrap">Date of skin screening:</p>
              <input class="pdfInput datepicker"  
                  type="text" name="date_screen_care" id="" value="<?php echo $appointment_date;?>" />
            </div>
            <div class="mb-2 d-flex align-items-end">
              <p class="mb-0 text-nowrap">Name:</p>
              <input class="pdfInput" type="text" name="name" id="" value="<?php echo $fname;?>" />
            </div>
            <div class="mb-2 d-flex align-items-end">
              <p class="mb-0 text-nowrap">DOB:</p>
              <input class="pdfInput datepicker"  
                  type="text" name="dob" id="" value="<?php echo $dob;?>" />
              <p class="mb-0 text-nowrap">Age:</p>
              <input class="pdfInput" type="text" name="age" id="" value="<?php echo $age;?>"  />
              <p class="mb-0 text-nowrap">Phone Number:</p>
              <input class="pdfInput" type="text" name="phone" id=""  value="<?php echo $phone;?>" />
            </div>
            <div class="my-4">
              <strong>
                Our clinician has indicated area(s) that require further
                examination and <br />
                follow-up with the participants own doctor or specialist
              </strong>
            </div>
              
            <div class="canvaDiv" >
                   <section class="section4">
        <div class="container">
         
          <div class="innerContainer examinationType">
           
            <div class="canvaContainer">
              <div class="innerCanva">
                <div class="grid-container">
                  <div class="grid-item item-1">
                 
                       <canvas id="frontCanvas" width="300" height="300"></canvas>
                   
                   
                     
                     
                  </div>
                  <div class="grid-item item-2">
                  
                    <canvas id="backCanvas" width="300" height="300"></canvas>
                      <input name="backCanvas" type="hidden" id="backCanvas_point" value="<?php if(isset($body_chart_data['backCanvas'])) echo $body_chart_data['backCanvas']; else echo '';?>">
                    
                  </div>
                   
                </div>
                      <div class="grid-container">
                             <div class="grid-item item-3">
                   
                        <canvas id="face1Canvas" width="300" height="300"></canvas>
                        <input name="face1Canvas" type="hidden" id="face1Canvas_point" value="<?php if(isset($body_chart_data['face1Canvas'])) echo $body_chart_data['face1Canvas']; else echo '';?>">
                   
                  </div>
                  <div class="grid-item item-4">
                   
                        <canvas id="face2Canvas" width="300" height="300"></canvas>
                      <input name="face2Canvas" type="hidden" id="face2Canvas_point" value="<?php if(isset($body_chart_data['face2Canvas'])) echo $body_chart_data['face2Canvas']; else echo '';?>">
                    
                  </div>
                  </div>
              </div>
            </div>
          </div>
        
        </div>
      </section>
         
            </div>
            <div class="parts my-4 populatefield">
              <div class="mb-2 sd-flex align-items-end">
                <p class="mb-0 text-nowrap">Location(s):</p>
                   <?php echo get_dropdown_with_field_map('Referral:Locations','Select a Locations(s)','localtion_1',$body_chart_data['populate_localtion_1']); ?>
              </div>
              <div class="mb-2 d-flex align-items-start">
                <p class="mb-0 mt-1 fw-bold me-2 text-nowrap"></p>
                <textarea class="pdfInput" type="text" name="localtion_1"><?php  if(isset($body_chart_ref_data['localtion_1']) && $body_chart_ref_data['localtion_1']){ echo $body_chart_ref_data['localtion_1'];}else { echo $body_chart_data['localtion_1'];}?></textarea>
              </div>
               <?php echo ai_improve_button('localtion_1'); ?>
            <!--  <div class="mb-2 d-flex align-items-start">
                <p class="mb-0 mt-1 fw-bold me-2 text-nowrap"></p>
                <textarea class="pdfInput" type="text" name="localtion_2"><?php // if(isset($body_chart_ref_data['localtion_2']) && $body_chart_ref_data['localtion_2']){ echo $body_chart_ref_data['localtion_2'];} else { echo $body_chart_data['localtion_2'];}?></textarea>
              </div>-->
            </div>
            <div class="parts my-4 populatefield">
              <div class="mb-2 sd-flex align-items-end">
                <p class="mb-0">
                  Clinical history and features:
                  <small>
                    <i>Colour, Shape, Border, Size, Surface, Symptoms </i>
                  </small>
                </p>
                    <?php echo get_dropdown_with_field_map('Referral:History','Select a Clinical History','clinical_1',$body_chart_data['populate_clinical_1']); ?>
              </div>
              <div class="mb-2 d-flex align-items-start">
                <p class="mb-0 mt-1 fw-bold me-2 text-nowrap"></p>
                <textarea class="pdfInput" type="text" name="clinical_1"><?php  if(isset($body_chart_ref_data['clinical_1']) && $body_chart_ref_data['clinical_1']){ echo $body_chart_ref_data['clinical_1'];} else { echo $body_chart_data['clinical_1'];}?></textarea>
              </div>
              <?php echo ai_improve_button('clinical_1'); ?>
            <!--  <div class="mb-2 d-flex align-items-start">
                <p class="mb-0 mt-1 fw-bold me-2 text-nowrap"></p>
                <textarea class="pdfInput" type="text" name="clinical_2"><?php // if(isset($body_chart_ref_data['clinical_2']) && $body_chart_ref_data['clinical_2']){ echo $body_chart_ref_data['clinical_2'];} else { echo $body_chart_data['clinical_2'];}?></textarea>
              </div>-->
            </div>
            <div class="parts my-4 populatefield">
              <div class="mb-2 d-sflex align-items-end">
                <p class="mb-0">
                  Dermatoscopic features:
                  <small>
                    <i
                      >Pigmented or Non-Pigmented, Pattern(s), Colour(s) Clues
                      to Malignancy</i
                    >
                  </small>
                </p>
                     <?php echo get_dropdown_with_field_map('Referral:Features','Select a Dermatoscopic Features','dermatoscopic_1',$body_chart_data['populate_dermatoscopic_1']); ?>
              </div>
              <div class="mb-2 d-flex align-items-start">
                <p class="mb-0 mt-1 fw-bold me-2 text-nowrap"></p>
                <textarea class="pdfInput" type="text" name="dermatoscopic_1"><?php  if(isset($body_chart_ref_data['dermatoscopic_1']) && $body_chart_ref_data['dermatoscopic_1']){ echo $body_chart_ref_data['dermatoscopic_1'];} else { echo $body_chart_data['dermatoscopic_1'];}?></textarea>
              </div>
              <?php echo ai_improve_button('dermatoscopic_1'); ?>
         <!--     <div class="mb-2 d-flex align-items-start">
                <p class="mb-0 mt-1 fw-bold me-2 text-nowrap"></p>
                <textarea class="pdfInput" type="text" name="dermatoscopic_2"><?php  //if(isset($body_chart_ref_data['dermatoscopic_2']) && $body_chart_ref_data['dermatoscopic_2']){ echo $body_chart_ref_data['dermatoscopic_2']; } else { echo $body_chart_data['dermatoscopic_2'];}?></textarea>
              </div>-->
            </div>
            <div class="parts my-4">
              <div class="d-flex align-items-center justify-content-center" style="width:100%;">
                <div style="width:100%;max-width:900px;">
                  <input type="hidden" id="photo_file_url_ref" name="uploaded_file_url_ref" value="<?php if (isset($body_chart_ref_data['uploaded_file_url_ref']) && $body_chart_ref_data['uploaded_file_url_ref']) echo $body_chart_ref_data['uploaded_file_url_ref']; elseif (isset($body_chart_data['uploaded_file_url']) && $body_chart_data['uploaded_file_url']) echo $body_chart_data['uploaded_file_url']; ?>">
                  <div id="file_preview_ref" style="display:flex;flex-wrap:nowrap;justify-content:center;gap:15px;width:100%;">
                    <?php
                    $uploaded_files_ref = isset($body_chart_ref_data['uploaded_file_url_ref']) ? $body_chart_ref_data['uploaded_file_url_ref'] : '';
                    // Fall back to body chart data if no ref data
                    if (empty($uploaded_files_ref) && isset($body_chart_data['uploaded_file_url'])) {
                      $uploaded_files_ref = $body_chart_data['uploaded_file_url'];
                    }
                    
                    $files = !empty($uploaded_files_ref) ? array_filter(explode(',', $uploaded_files_ref)) : array();
                    
                    // Always show 4 slots
                    for ($i = 0; $i < 4; $i++) {
                      $hasImage = isset($files[$i]) && !empty($files[$i]);
                      $emptyClass = $hasImage ? '' : ' ref-image-empty';
                      echo '<div class="ref-image-slot' . $emptyClass . '" style="width:200px;height:200px;border:20px solid #98D9C2;box-sizing:content-box;display:flex;align-items:center;justify-content:center;background:#fff;position:relative;">';
                      if ($hasImage) {
                        echo '<img style="width:200px;height:200px;object-fit:contain;" src="' . esc_url($files[$i]) . '">';
                        echo '<a href="#" class="remove_file_ref removefrompdf" data-url="' . esc_url($files[$i]) . '" style="position:absolute;top:-15px;right:-15px;background:red;color:white;padding:2px 8px;border-radius:50%;font-size:14px;text-decoration:none;font-weight:bold;">×</a>';
                      } else {
                        echo '<span style="color:#999;font-size:24px;font-weight:bold;">N/A</span>';
                      }
                      echo '</div>';
                    }
                    ?>
                  </div>
                  <div style="text-align:center;margin-top:15px;">
                    <button id="upload_file_button_ref" class="removefrompdf button button-primary">Attach Images</button>
                    <input type="file" id="image_file_ref" style="display: none;" accept=".jpg,.jpeg,.png,.JPG,.JPEG,.PNG" multiple />
                  </div>
                </div>
              </div>
              <div class="mb-2">
                <br>
                  <?php 
                   $prevsignature=get_user_meta((int)get_current_user_id(),'signature_img',true);
               if(isset($body_chart_ref_data['uploaded_sig_url']) && $body_chart_ref_data['uploaded_sig_url']){
                  $signature= $body_chart_ref_data['uploaded_sig_url'];
               }
                    else{
                        $signature=  $prevsignature;
                    }
                   
                  ?>
                  <input type="hidden" name="uploaded_sig_url" id="uploaded_sig_url" value=" <?php  if($signature){ echo $signature; } ?>">
                      <div id="signatureimgsec">
                           <div id="signatureimg">
                               <?php  if($signature){ ?>
                             <img style="width: 300px;height: auto;" class="refimg" src="<?php echo $signature;?>" alt="">
                               <?php } ?>
                          </div>
                     
                            
                  <div class="d-flex">
                       <button id="upload_file_button" type="button" class="removefrompdf button button-primary">Upload Signature</button>
                    <button id="resetimg" class="removefrompdf <?php  if($signature){ } else {echo 'd-none';}?>">Reset</button>
                  </div>
                    </div>
                <div class="d-flex <?php  if(isset($body_chart_ref_data['uploaded_sig_url']) && $body_chart_ref_data['uploaded_sig_url']){ echo 'd-none';}?>" id="canvas_sec">
                
                  <div id="signature" class="mb-0 borderBottomDotted" style="width: 300px; min-height: 100px"></div>
                  
                  <div class="restBtn">
                    <button id="resetSignature" class="removefrompdf">Reset</button>
                  </div>
                </div>
               
              </div>
            </div>
            <div class="footerDiv mb-3" style="padding-bottom: 20px;">
              <!-- <img class="w-100" src="<?php //echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/Screenshot_2Footer.png" alt="" /> -->
              <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/referral-document-footer.jpg" alt="" />
            </div>
              <input type="hidden" name="appoinment_id" id="appoinment_id" value="<?php echo $id;?>">
                 <div class="amlia_form_btns removefrompdf sticky-bottom bg-white py-2">
        <button class="btn btn-primary" name="submit" type="submit" id="save_body_chart_ref">Save</button>
        <button class="btn btn-primary" id="pdf_generator" type="button"  id="generate_pdf">Pdf Generate</button>
          
     
        <button class="btn btn-primary"  id="openModalBtn" type="button" >Send Report</button>
     <!--   <button class="btn btn-primary" id="manual_report_send" id="save_send_email">Manual & Send Report</button>-->
    </div>
              <input type="hidden" id="signatureData" name="signatureData" />
          </form>
            
        </div>
          
      </section>
          </div>
    </main>
<div id="emailModal" class="modal">
    <div class="modal-content">
      <button type="button" class="btn-close modal-close-icon" id="closeModalBtn" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      <h2>Add Email Addresses</h2>
      <input class="w-100" type="email" id="emailInput" placeholder="Enter email" />
      <button class="w-100 mx-0 btn-success btn" id="addEmailBtn">Add Email</button>
      
      <ul class="email-list w-100" id="emailList">
          <?php if($email){ ?>
        <li><?php echo $email;?><button data-email="<?php echo $email;?>" class="remove-icon" title="Remove Email" fdprocessedid="n3c7h59">×</button></li>
          <?php } ?>
        </ul>
      <button class="btn btn-success mx-0"  id="save_send_email_ref" type="button" >Save & Send Report</button>
    </div>
  </div>
<script>
    const textareas = document.getElementsByTagName('textarea');

    Array.from(textareas).forEach((textarea) => {
        textarea.addEventListener('input', function () {
            this.style.height = 'auto'; 
            this.style.height = this.scrollHeight + 'px'; 
        });
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    });
</script>
    <script>
      // Convert the div into a canvas for signature
      const signatureDiv = document.getElementById("signature");

      // Create a canvas element and replace the div
      const canvas = document.createElement("canvas");
      canvas.width = 300; // Match the div's width
      canvas.height = 100; // Match the div's minimum height
      canvas.style.borderBottom = "1px dotted"; // Retain the dotted border effect
      signatureDiv.replaceWith(canvas);

      const ctx = canvas.getContext("2d");
      ctx.lineWidth = 2; // Set the line thickness
      ctx.lineJoin = "round";
      ctx.lineCap = "round";

      let isDrawing = false;

      // Array to store canvas states for undo
      const undoStack = [];

      // Save the current state of the canvas
      function saveState() {
        undoStack.push(canvas.toDataURL());
      }

      // Restore the last state
      function undo() {
        if (undoStack.length > 0) {
          const img = new Image();
          img.src = undoStack.pop();
          img.onload = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height); // Clear the canvas
            ctx.drawImage(img, 0, 0); // Redraw the last saved state
          };
        }
      }

      // Start drawing
      canvas.addEventListener("mousedown", (e) => {
        saveState(); // Save the current state before drawing
        isDrawing = true;
        ctx.beginPath();
        ctx.moveTo(e.offsetX, e.offsetY);
      });

      // Draw
      canvas.addEventListener("mousemove", (e) => {
        if (isDrawing) {
          ctx.lineTo(e.offsetX, e.offsetY);
          ctx.stroke();
        }
      });

      // Stop drawing
      canvas.addEventListener("mouseup", () => {
        isDrawing = false;
      });

      // Stop drawing if the mouse leaves the canvas
      canvas.addEventListener("mouseleave", () => {
        isDrawing = false;
      });

      // Listen for Ctrl + Z to undo
      document.addEventListener("keydown", (e) => {
        if (e.ctrlKey && e.key === "z") {
          undo();
        }
      });

      document.getElementById("resetSignature").addEventListener("click", (event) => {
        event.preventDefault(); // Prevent the default form behavior
        ctx.clearRect(0, 0, canvas.width, canvas.height); // Clear the canvas
          jQuery('#uploaded_sig_url').val('');
            jQuery('#signatureimg').html('');
            jQuery('#resetimg').addClass('d-none');
      });
        document.getElementById("resetimg").addEventListener("click", (event) => {
         event.preventDefault();
          jQuery('#uploaded_sig_url').val('');
            jQuery('#signatureimg').html('');
            jQuery('#resetimg').addClass('d-none');
            jQuery('#canvas_sec').removeClass('d-none');
        });
    </script>

<script>
         jQuery(document).ready(function ($) {
            $('.select2drop').select2();
            $('.select2drop').each(function() {
            var $this = $(this);
            var placeholder = $this.find('option[value="0"]').text(); // Get the text of the first option
            
            $this.select2({
                placeholder: placeholder, // Set the dynamic placeholder
                allowClear: true           // Allow clearing the selection
            });
        });
         });
          const images = {
              frontCanvas: '<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/front-part.png',

              backCanvas: '<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/back-part.png',
              face1Canvas: '<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/view-face1.png',
              face2Canvas: '<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/view-face2.png'
          };

      const markPositionsField = document.getElementById('markPositions');
      const marks = {
              frontCanvas: [],
              face1Canvas: [],
              face2Canvas: [],
              backCanvas: []
          };

       Object.keys(images).forEach(canvasId => {
              const canvas = document.getElementById(canvasId);
              const ctx = canvas.getContext('2d');
              const img = new Image();
              img.src = images[canvasId];
              img.onload = () => {
                  ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
              };

              //
            /*  canvas.addEventListener('click', (event) => {
                  const rect = canvas.getBoundingClientRect();
                  const x = event.clientX - rect.left;
                  const y = event.clientY - rect.top;
                  marks[canvasId].push({ x, y });
                  drawMarks(canvasId);

                 
              });*/
          });
      function drawMarks(canvasId) {

              const canvas = document.getElementById(canvasId);
              const ctx = canvas.getContext('2d');
              const img = new Image();
              img.src = images[canvasId];
              img.onload = () => {
                  ctx.clearRect(0, 0, canvas.width, canvas.height);
                  ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                  console.log(marks);
                  marks[canvasId].forEach(mark => {
                      ctx.beginPath();
                      ctx.arc(mark.x, mark.y, 8, 0, 2 * Math.PI);
                      ctx.fillStyle = 'red';
                      ctx.fill();
                      ctx.stroke();
                  });
              };
          jQuery('#markPositions').val(JSON.stringify(marks));
          }
       if(markPositionsField.value){
          var dataFromDB='<?php echo $body_chart_data['mark_positions'];?>';
          const cleanData = dataFromDB.replace(/<[^>]*>/g, '');
          //alert(cleanData);
            const data = JSON.parse(cleanData || '[]');
           console.log(marks);
           //drawMarksByData(premarks);
        function markPositionsOnCanvas(canvasId, positions) {
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext('2d');

    // Draw a red dot at each position
    positions.forEach(position => {
      ctx.beginPath();
      ctx.arc(position.x, position.y, 8, 0, 2 * Math.PI);  // Draw a circle with radius 5
      ctx.fillStyle = 'red';
      ctx.fill();
    });
      }
      // Mark positions on each canvas based on the data
  function markAllPositions() {
     // alert(data.backCanvas.length);
    // Check and mark positions on the front canvas
    if (data.frontCanvas.length > 0) {
      markPositionsOnCanvas('frontCanvas', data.frontCanvas);
    }

    // Check and mark positions on the back canvas
    if (data.backCanvas.length > 0) {

      markPositionsOnCanvas('backCanvas', data.backCanvas);
    }

    // Similarly for other canvases like face1Canvas and face2Canvas (if they have positions)
    if (data.face1Canvas.length > 0) {
      markPositionsOnCanvas('face1Canvas', data.face1Canvas);
    }

    if (data.face2Canvas.length > 0) {
      markPositionsOnCanvas('face2Canvas', data.face2Canvas);
    }
  }
  setTimeout(function(){
  // Call the function to mark positions when the page loads
  markAllPositions();
       }, 2000);
      }
        function undoMark(canvasId) {
              if (marks[canvasId].length > 0) {
                  marks[canvasId].pop(); //
                  drawMarks(canvasId);
              }
          }
      function loadMarks() {

    marks.forEach(mark => {
      drawMark(mark.x, mark.y);
    });
  }

// Check on dropdown change
 
</script>
 <script>
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const emailModal = document.getElementById('emailModal');
    const addEmailBtn = document.getElementById('addEmailBtn');
    const emailInput = document.getElementById('emailInput');
    const emailList = document.getElementById('emailList');
    const emailField = document.getElementById('report_to_email');
     //const emailField = document.getElementById('remove-icon');

    let emails = [];

    // Show the modal
       jQuery('.remove-icon').on('click', function (e) {
      jQuery(this).closest('li').remove();
            var semail=jQuery(this).data('email');
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

    // Handle form submission
   /* document.getElementById('emailForm').addEventListener('submit', (e) => {
      e.preventDefault(); // Prevent actual submission for demonstration
      alert(`Submitted Emails: ${emailField.value}`);
    });*/
      jQuery(document).ready(function ($) {
          var previousemail='<?php echo $email;?>';
          emails.push(previousemail);
    let file_frame;
    let file_frame_ref;

    // Referral Photo Upload (2x2 Grid)
    $('#upload_file_button_ref').on('click', function (e) {
        e.preventDefault();

        // If the uploader object has already been created, reopen it.
        if (file_frame_ref) {
            file_frame_ref.open();
            return;
        }

        // Create the uploader object for multiple images
        file_frame_ref = wp.media.frames.file_frame_ref = wp.media({
            title: 'Select Photos (Up to 4)',
            button: {
                text: 'Add Selected Photos'
            },
            multiple: true // Allow multiple files
        });

        // On file selection
        file_frame_ref.on('select', function () {
            const attachments = file_frame_ref.state().get('selection').toJSON();
            let existingUrls = $('#photo_file_url_ref').val();
            let urlArray = existingUrls ? existingUrls.split(',').filter(u => u.trim()) : [];
            
            // Add new attachments (max 4 total)
            attachments.forEach(function(attachment) {
                if (urlArray.length < 4 && attachment.type === 'image') {
                    urlArray.push(attachment.url);
                    
                    // Auto-save to historical photos database
                    if (typeof window.savePhotoToHistory === 'function') {
                      window.savePhotoToHistory(attachment.url, 'referral', null, null);
                    }
                }
            });
            
            // Limit to 4
            urlArray = urlArray.slice(0, 4);
            
            // Update hidden field
            $('#photo_file_url_ref').val(urlArray.join(','));
            
            // Update preview
            updateRefImagePreview(urlArray);
        });

        // Open the uploader dialog
        file_frame_ref.open();
    });
    
    // Function to update the image preview - single row with green borders
    function updateRefImagePreview(urlArray) {
        var previewHtml = '';
        
        // Always show 4 slots
        for (var i = 0; i < 4; i++) {
            var hasImage = urlArray[i] && urlArray[i].trim() !== '';
            var emptyClass = hasImage ? '' : ' ref-image-empty';
            previewHtml += '<div class="ref-image-slot' + emptyClass + '" style="width:200px;height:200px;border:20px solid #98D9C2;box-sizing:content-box;display:flex;align-items:center;justify-content:center;background:#fff;position:relative;">';
            if (hasImage) {
                previewHtml += '<img style="width:200px;height:200px;object-fit:contain;" src="' + urlArray[i] + '">';
                previewHtml += '<a href="#" class="remove_file_ref removefrompdf" data-url="' + urlArray[i] + '" style="position:absolute;top:-15px;right:-15px;background:red;color:white;padding:2px 8px;border-radius:50%;font-size:14px;text-decoration:none;font-weight:bold;">×</a>';
            } else {
                previewHtml += '<span style="color:#999;font-size:24px;font-weight:bold;">N/A</span>';
            }
            previewHtml += '</div>';
        }
        
        $('#file_preview_ref').html(previewHtml);
    }
    
    // Remove image from grid
    $(document).on('click', '.remove_file_ref', function(e) {
        e.preventDefault();
        var urlToRemove = $(this).data('url');
        var existingUrls = $('#photo_file_url_ref').val();
        var urlArray = existingUrls ? existingUrls.split(',').filter(u => u.trim()) : [];
        
        // Remove the URL
        urlArray = urlArray.filter(u => u !== urlToRemove);
        
        // Update hidden field
        $('#photo_file_url_ref').val(urlArray.join(','));
        
        // Update preview
        updateRefImagePreview(urlArray);
    });

    $('#upload_file_button').on('click', function (e) {
        e.preventDefault();

        // If the uploader object has already been created, reopen it.
        if (file_frame) {
            file_frame.open();
            return;
        }

        // Create the uploader object
        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select or Upload a File',
            button: {
                text: 'Use this file'
            },
            multiple: false // Only select one file
        });

        // On file selection, update the input field and preview
        file_frame.on('select', function () {
            const attachment = file_frame.state().get('selection').first().toJSON();
            $('#uploaded_sig_url').val(attachment.url);
          
            if (attachment.type === 'image') {
                jQuery('#resetimg').removeClass('d-none');
                 jQuery('#canvas_sec').addClass('d-none');
                $('#signatureimg').html(
                    `<img style="width: 300px;height: auto;" class="refimg" src="`+attachment.url+`" alt="">`
                );
            }
        });

        // Open the uploader dialog
        file_frame.open();
    });
});
  </script>
<?php } ?>
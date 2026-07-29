<?php
$id = isset($_GET['id']) ? $_GET['id'] : '';
if ($id) {
  $infoappointment = get_appoinment_booking_details($id);
  //print_r($infoappointment);
  $info = isset($infoappointment['info']) ? json_decode($infoappointment['info'], true) : array();
  //print_r($info);
  $fname = isset($info['firstName']) ? $info['firstName'] : $infoappointment['firstName'];
  $lname = isset($info['lastName']) ? $info['lastName'] : $infoappointment['lastName'];
  $phone = isset($info['phone']) ? $info['phone'] : $infoappointment['phone'];
  $email = isset($infoappointment['email']) ? $infoappointment['email'] : '';
  $gender = isset($infoappointment['gender']) ? $infoappointment['gender'] : '';
  $email = isset($infoappointment['email']) ? $infoappointment['email'] : '';
  $serviceid = isset($infoappointment['serviceId']) ? $infoappointment['serviceId'] : '';
  $question_answer = isset($infoappointment['question_answer']) ? json_decode($infoappointment['question_answer'], true) : array();
  $address = isset($question_answer[38]['value']) ? $question_answer[38]['value'] : '';
  $ppe = isset($question_answer[49]['value']) ? $question_answer[49]['value'] : '';
  // print_r($question_answer);
  $gender = isset($question_answer[42]['value']) ? $question_answer[42]['value'] : '';
  $department = isset($question_answer[43]['value']) ? $question_answer[43]['value'] : '';
  $employment_type = isset($question_answer[44]['value']) ? $question_answer[44]['value'] : '';
  $descent = isset($question_answer[45]['value']) ? $question_answer[45]['value'] : '';
  // print_r($question_answer);
  $age = isset($question_answer[5]['value']) ? $question_answer[5]['value'] : '';
  $serviceinfo = get_appoinment_service_details($serviceid);
  $referer_url = admin_url('admin.php?page=amelia-booking');
  $body_chart = get_appoinment_body_chart($id);
  $body_chart_data = json_decode($body_chart->data, true);
  $level_risk = isset($body_chart_data['level_risk']) ? $body_chart_data['level_risk'] : 'low';
  $level_risk_value = isset($body_chart_data['level_risk_value']) ? $body_chart_data['level_risk_value'] : '-55';
  // print_r($body_chart_data);
  $skincancerQuestion = array(6 => 'A personal or family history of melanoma? (family refers to immediate
biological members – a parent, sibling or child', 9 => 'Have you ever been diagnosed with any form of skin cancer in the past?', 48 => 'Do you have more than 50 moles on your body?', 31 => 'Do you have a compromised (weakened) immune system often caused by underlying disease or immunosuppressant medications / treatments?', 50 => 'Do you have a history of high amounts of sun exposure with 5 or more sunburns particularly during childhood? Sunburn varies in degree of damage and generally means red, hot, painful skin following sun exposure?', 51 => 'Do you have a history of multiple peeling or blistering sunburns?', 5 => 'Are you older than 60 years?');
  //$higher_risk=array('Fairer skin? - tends to burn rather than tan?','Evidence of sun damage? – uneven skin texture or colour, loss of skin tone, red
  //scaly spots (actinic / solar keratosis) or flat brown spots, deep wrinkles, broken
  //blood vessels on the skin surface','Spend a significant time outdoors? – working, recreationally – hobbies or
  //sports etc?');
  $higher_risk = array(52 => 'Do you have fairer skin that tends to burn rather than tan?', 53 => 'Do you have evidence of sun damage such as uneven skin texture or colour, loss of skin tone, red scaly spots (actinic / solar keratosis) or flat brown spots, deep wrinkles, broken blood vessels on the skin surface?', 16 => 'Do you spend significant time outdoors either working, recreationally such as hobbies or sports etc?');
  if (isset($body_chart_data['email']) && $body_chart_data['email']) {
    $email = $body_chart_data['email'];
  }
?>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
  <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>-->
  <form method="post" id="body_chart_form">
    <a class="btn btn-primary" href="<?php echo $referer_url; ?>">Go Back</a>
    <div class="justify-content-space-between">
      <h2 class="section_heading">Service: <?php echo  $serviceinfo->name; ?></h2>
    </div>
    <main class="pdfHtml" id="content-to-pdf">
      <input type="hidden" id="bodypage" value="1">
      <section class="section1 pdf-capture">


        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-1.jpg" alt="" />
        </div>
      </section>
      <section class="section2 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-3-1.jpg" alt="" />
          <div class="innerContainer">
            <h2 class="sectionTitle">PERSONAL PROFILE</h2>
            <div class="formInput">
              <!-- <h2>PERSONAL PROFILE</h2> -->
              <label>Name</label>
              <input type="text" name="name" value="<?php if (isset($body_chart_data['name']) && $body_chart_data['name']) echo $body_chart_data['name'];
                                                    else echo $fname . ' ' . $lname; ?>" id="" placeholder="Name" />
              <label>Customer Email</label>
              <input type="email" name="email" value="<?php if (isset($body_chart_data['email'])) echo $body_chart_data['email'];
                                                      else echo $email; ?>" id="body_report_to_email">
              <input type="hidden" name="report_email" value="<?php if (isset($body_chart_data['report_email'])) echo $body_chart_data['report_email'];
                                                              else echo $email; ?>" id="report_to_email">
              <label>Age</label>
              <input type="text" name="age" id="" placeholder="Age" value="<?php if (isset($body_chart_data['age']) && $body_chart_data['age']) echo $body_chart_data['age'];
                                                                            else echo $age; ?>" />
              <label>Gender</label>

              <select name="gender" class="select_arrow"
                style="
                          min-width: 130px;
                          margin-bottom: 0;
                          width: 100%;
                          max-width: 100%;
                          height: 100%;
                          background: transparent url('https://onsite.skinchx.com.au/wp-content/plugins/amelia-addon//admin/images/downSolid.svg');
                          background-size: 20px 20px;
                          -webkit-appearance: none;
                          -moz-appearance: none;
                          appearance: none;
                          background-position-x: right;
                          background-position-y: center;
                          background-repeat: no-repeat;
                          vertical-align: middle;
                          ">
                <option value=""></option>
                <option value="Male" <?php if (isset($body_chart_data['gender']) && $body_chart_data['gender'] == 'Male') echo 'selected';
                                      else {
                                        if ($gender == 'Male' && !isset($body_chart_data['gender'])) echo 'selected';
                                      } ?>>Male</option>
                <option value="Female" <?php if (isset($body_chart_data['gender']) && $body_chart_data['gender'] == 'Female') echo 'selected';
                                        else {
                                          if ($gender == 'Female'  && !isset($body_chart_data['gender'])) echo 'selected';
                                        } ?>>Female</option>
              </select>
              <label>Address</label>
              <input type="text" name="location" id="" placeholder="Location" value="<?php if (isset($body_chart_data['location']) && $body_chart_data['location']) echo $body_chart_data['location'];
                                                                                      else echo $address; ?>" />
              <label>Department</label>
              <input type="text" name="department" value="<?php if (isset($body_chart_data['department'])) echo $body_chart_data['department'];
                                                          else echo $department; ?>" id="" placeholder="Department" />
              <label>Phone Number</label>
              <input type="text" name="phone" value="<?php if (isset($body_chart_data['phone'])) echo $body_chart_data['phone'];
                                                      else echo $phone; ?>" id="" placeholder="Phone Number" />
              <label>Are you Aboriginal, Torres Strait Islander, Pacific Islander or Maori descent?</label>
              <select name="description"
                class="select_arrow"
                style="
                          min-width: 130px;
                          margin-bottom: 0;
                          width: 100%;
                          max-width: 100%;
                          height: 100%;
                          background: transparent url('https://onsite.skinchx.com.au/wp-content/plugins/amelia-addon//admin/images/downSolid.svg');
                          background-size: 20px 20px;
                          -webkit-appearance: none;
                          -moz-appearance: none;
                          appearance: none;
                          background-position-x: right;
                          background-position-y: center;
                          background-repeat: no-repeat;
                          vertical-align: middle;
                          ">
                <option value=""></option>
                <option value="Yes" <?php if (isset($body_chart_data['description']) && $body_chart_data['description'] == 'Yes') echo 'selected';
                                    elseif ($descent == 'Yes, one of these.' && !isset($body_chart_data['description'])) echo 'selected'; ?>>Yes</option>
                <option value="No" <?php if (isset($body_chart_data['description']) && $body_chart_data['description'] == 'No') echo 'selected';
                                    elseif ($descent == 'No, none of these.' && !isset($body_chart_data['description'])) echo 'selected'; ?>>No</option>

              </select>
              <?php
              $saved_date = $body_chart_data['date_assesment']; // Fetch saved value


              ?>
              <label>Date of Assesment</label>
              <input class="datepicker"
                type="text"
                name="date_assesment"
                id=""
                value="<?php echo $saved_date; ?>"

                placeholder="Date of Assessment" />

            </div>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-2-2.jpg" alt="" />
        </div>
      </section>
      <section class="section3 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-3-1.jpg" alt="" />

          <div class="innerContainer">
            <div class="tableContainer">
              <i>
                If you answered <strong>yes</strong> to the any of the below
                questions, you have a higher risk of developing a skin cancer.
              </i>
              <table>
                <thead>
                  <tr>
                    <td>Question</td>
                    <td class="text-nowrap">YOUR RESPONSE</td>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $question_data_map = array();
                  if (!empty($question_answer)) {

                    foreach ($question_answer as $answer) {
                      if ($answer['label'] !== 'Date') {
                        if (is_array($answer['value'])) {
                          $answer_value = implode(', ', array_map('htmlspecialchars', $answer['value']));
                        } elseif (is_string($answer['value'])) {
                          // If it's a string, print it directly
                          $answer_value = ($answer['value']);
                        } else {
                          // If it's a string, print it directly
                          $answer_value = $answer['value'];
                        }
                        $question_data_map[$answer['label']] = $answer_value;
                  ?>

                  <?php }
                    }
                  } ?>
                  <?php $r_index = 1;
                  if (!empty($skincancerQuestion)) {
                    // print_r($skincancerQuestion);
                    foreach ($skincancerQuestion as $map => $skinquestion) {
                      if ($map == 6) {
                        $skinquestion = 'Do you have a first-degree relative that has been diagnosed with Melanoma?';
                      }
                      if ($map == 9) {
                        $skinquestion = 'Do you have a personal history of skin cancers?';
                      }
                      //                            if($map==47){
                      //                               $skinquestion='Do you have a lot of atypical / unusual looking moles?';
                      //                           }
                      $alloptionsRisk = array('Yes', 'No');


                      $skinquestion_ans = isset($question_answer[$map]['value']) ? $question_answer[$map]['value'] : '';

                      if ($map == 48) {
                        $risk_value_Arra = array('Yes, way more', 'Yes, about 50', 'Unsure, maybe yes');
                        if (in_array($skinquestion_ans, $risk_value_Arra)) {
                          $highRisk = 'Yes';
                          $level_risk = 'High';
                          $level_risk_value = 55;
                        } else {
                          $highRisk = '';
                        }
                      }

                      if ($skinquestion == 'Are you older than 60 years?') {
                        $skinquestion_ans = ($skinquestion_ans == '60-75' || $skinquestion_ans == '75+') ? 'Yes' : 'No';
                      }
                  ?>
                      <tr>
                        <td><?php echo $skinquestion; ?></td>
                        <td>



                          <select dataval="<?php echo $higher_risk_question_ans; ?>"
                            style="
                           min-width: 130px;
                          margin-bottom: 0;
                          width: 100%;
                          max-width: 100%;
                          height: 100%;
                          border: none;
                          font-size: 20px;
                          background: transparent url('https://onsite.skinchx.com.au/wp-content/plugins/amelia-addon//admin/images/downSolid.svg');
                          background-size: 20px 20px;
                          -webkit-appearance: none;
                          -moz-appearance: none;
                          appearance: none;
                          background-position-x: right;
                          background-position-y: center;
                          background-repeat: no-repeat;
                          vertical-align: middle;
                           "
                            name="higher_risk_option_<?php echo $h_index; ?>" class="higher_risk_option">
                            <option value=""></option>
                            <?php if ($map == 48) {
                              foreach ($alloptionsRisk as $riskop) {
                                if ($riskop == $skinquestion_ans && !isset($body_chart_data['risk_option_' . $r_index . '']) || isset($body_chart_data['risk_option_' . $r_index . '']) && $body_chart_data['risk_option_' . $r_index . ''] == $riskop) {
                                  echo '<option value="' . $riskop . '" selected uservalue="' . $skinquestion_ans . '">' . $riskop . '</option>';
                                } else {
                                  echo '<option value="' . $riskop . '">' . $riskop . '</option>';
                                }
                              }
                            } else {
                            ?>

                              <option value="Yes" <?php if ($skinquestion_ans == 'Yes' && !isset($body_chart_data['risk_option_' . $r_index . '']) || isset($body_chart_data['risk_option_' . $r_index . '']) && $body_chart_data['risk_option_' . $r_index . ''] == 'Yes') echo 'selected'; ?>>Yes</option>
                              <option value="No" <?php if ($skinquestion_ans == 'No' && !isset($body_chart_data['risk_option_' . $r_index . '']) || isset($body_chart_data['risk_option_' . $r_index . '']) && $body_chart_data['risk_option_' . $r_index . ''] == 'No') echo 'selected'; ?>>No</option>
                            <?php } ?>
                          </select>

                        </td>
                      </tr>
                  <?php $r_index++;
                    }
                  } ?>

                </tbody>
              </table>
            </div>
            <div class="tableContainer">
              <i>
                If you answered <strong>yes</strong> to the any of the below
                questions, you are classified as an intermediate risk (unless
                you also answered yes to any of the above questions which would
                put you at higher risk)
              </i>
              <table>
                <thead>
                  <tr>
                    <td></td>
                    <td class="text-nowrap">YOUR RESPONSE</td>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($higher_risk)) {
                    $h_index = 1;

                    foreach ($higher_risk as $highmap => $higher_risk_question) {
                      //$$higher_risk_question_ans=isset($question_data_map[$higher_risk_question])?$question_data_map[$higher_risk_question]:'';
                      $higher_risk_question_ans = isset($question_answer[$highmap]['value']) ? $question_answer[$highmap]['value'] : '';
                      if ($highmap == 16) {
                        $sixfieldValue = isset($question_answer[16]['value']) ? $question_answer[16]['value'] : '';
                        $svnfieldValue = isset($question_answer[17]['value']) ? $question_answer[17]['value'] : '';

                        if ($sixfieldValue == 'Indoor worker so my exposure at work is incedental' || $sixfieldValue == 'No time outdoors' || $svnfieldValue == 'None') {
                          $higher_risk_question_ans = 'No';
                        } else {
                          $higher_risk_question_ans = 'Yes';
                        }
                      }
                  ?>
                      <tr>
                        <td><?php echo $higher_risk_question; ?></td>
                        <td>
                          <select dataval="<?php echo $higher_risk_question_ans; ?>"
                            style="
                          min-width: 130px;
                          margin-bottom: 0;
                          width: 100%;
                          max-width: 100%;
                          height: 100%;
                          border: none;
                          font-size: 20px;
                          background: transparent url('https://onsite.skinchx.com.au/wp-content/plugins/amelia-addon//admin/images/downSolid.svg');
                          background-size: 20px 20px;
                          -webkit-appearance: none;
                          -moz-appearance: none;
                          appearance: none;
                          background-position-x: right;
                          background-position-y: center;
                          background-repeat: no-repeat;
                          vertical-align: middle;
                          "
                            name="higher_risk_option_<?php echo $h_index; ?>" class="select_arrow higher_risk_option">
                            <option value=""></option>
                            <option value="Yes" <?php if ($higher_risk_question_ans == 'Yes' && !isset($body_chart_data['higher_risk_option_' . $h_index . '']) || isset($body_chart_data['higher_risk_option_' . $h_index . '']) && $body_chart_data['higher_risk_option_' . $h_index . ''] == 'Yes') echo 'selected'; ?>>Yes</option>
                            <option value="No" <?php if ($higher_risk_question_ans == 'No' && !isset($body_chart_data['higher_risk_option_' . $h_index . '']) || isset($body_chart_data['higher_risk_option_' . $h_index . '']) && $body_chart_data['higher_risk_option_' . $h_index . ''] == 'No') echo 'selected'; ?>>No</option>
                          </select>
                        </td>
                      </tr>
                  <?php $h_index++;
                    }
                  } ?>
                </tbody>
              </table>
              <i>
                If you answered <strong>no</strong> to all of the above
                questions, you have a lower risk of developing a skin cancer
              </i>
              <div class="tableContainer">
                <table>
                  <thead>
                    <tr>
                      <td></td>
                      <td class="text-nowrap">YOUR RESPONSE</td>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Do you feel the PPE that is provided to you by your employer is sufficient for your occupation and easily accessible?</td>
                      <td style="width: 300px;">
                        <select
                          class="select_arrow"
                          style="
                          min-width: 130px;
                          margin-bottom: 0;
                          width: 100%;
                          max-width: 100%;
                          height: 100%;
                          border: none;
                          font-size: 20px;
                          background: transparent url('https://onsite.skinchx.com.au/wp-content/plugins/amelia-addon//admin/images/downSolid.svg');
                          background-size: 20px 20px;
                          -webkit-appearance: none;
                          -moz-appearance: none;
                          appearance: none;
                          background-position-x: right;
                          background-position-y: center;
                          background-repeat: no-repeat;
                          vertical-align: middle;
                          "
                          name="ppe">
                          <option value=""></option>
                          <option value="NA" <?php if (isset($body_chart_data['ppe']) && $body_chart_data['ppe'] == 'NA') echo 'selected';
                                              else if ($ppe == 'NA' && !isset($body_chart_data['ppe'])) echo 'selected'; ?>>NA</option>
                          <option value="Yes" <?php if (isset($body_chart_data['ppe']) && $body_chart_data['ppe'] == 'Yes') echo 'selected';
                                              else if ($ppe == 'Yes' && !isset($body_chart_data['ppe'])) echo 'selected'; ?>>Yes</option>
                          <option value="No" <?php if (isset($body_chart_data['ppe']) && $body_chart_data['ppe'] == 'No') echo 'selected';
                                              else if ($ppe == 'No' && !isset($body_chart_data['ppe'])) echo 'selected'; ?>>No</option>

                          <option value="Somewhat Yes" <?php if (isset($body_chart_data['ppe']) && $body_chart_data['ppe'] == 'Somewhat Yes') echo 'selected';
                                                        else if ($ppe == 'Somewhat Yes' && !isset($body_chart_data['ppe'])) echo 'selected'; ?>>Somewhat Yes</option>

                          <option value="Somewhat No" <?php if (isset($body_chart_data['ppe']) && $body_chart_data['ppe'] == 'Somewhat No') echo 'selected';
                                                      else if ($ppe == 'Somewhat No' && !isset($body_chart_data['ppe'])) echo 'selected'; ?>>Somewhat No</option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>+ Does this individual need to be referred?</td>
                      <td style="width: 300px;">
                        <select
                          class="select_arrow"
                          style="
                          min-width: 130px;
                          margin-bottom: 0;
                          width: 100%;
                          max-width: 100%;
                          height: 100%;
                          border: none;
                          font-size: 20px;
                          background: transparent url('https://onsite.skinchx.com.au/wp-content/plugins/amelia-addon//admin/images/downSolid.svg');
                          background-size: 20px 20px;
                          -webkit-appearance: none;
                          -moz-appearance: none;
                          appearance: none;
                          background-position-x: right;
                          background-position-y: center;
                          background-repeat: no-repeat;
                          vertical-align: middle;
                          "
                          name="referal" class="referal_option" id="referal_option" onchange="referal_option_drop(this)">
                          <option value=""></option>
                          <option value="No" <?php if (isset($body_chart_data['referal']) && $body_chart_data['referal'] == 'No') echo 'selected'; ?>>No</option>
                          <option value="Yes with One month timeline" <?php if (isset($body_chart_data['referal']) && $body_chart_data['referal'] == 'Yes with One month timeline') echo 'selected'; ?>>Yes with One month timeline</option>
                          <option value="Yes with immediate timeline" <?php if (isset($body_chart_data['referal']) && $body_chart_data['referal'] == 'Yes with immediate timeline') echo 'selected'; ?>>Yes with immediate timeline</option>
                          <option value="Info Only" <?php if (isset($body_chart_data['referal']) && $body_chart_data['referal'] == 'Info Only') echo 'selected'; ?>>Info Only</option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>Did this person attend?</td>
                      <td style="width: 300px;"><select
                          class="select_arrow"
                          style="
                          min-width: 130px;
                          margin-bottom: 0;
                          width: 100%;
                          max-width: 100%;
                          height: 100%;
                          border: none;
                          font-size: 20px;
                          background: transparent url('https://onsite.skinchx.com.au/wp-content/plugins/amelia-addon//admin/images/downSolid.svg');
                          background-size: 20px 20px;
                          -webkit-appearance: none;
                          -moz-appearance: none;
                          appearance: none;
                          background-position-x: right;
                          background-position-y: center;
                          background-repeat: no-repeat;
                          vertical-align: middle;
                          "
                          name="attend" class="attend_option" id="attend_option">
                          <option value=""></option>
                          <option value="Yes" <?php if (isset($body_chart_data['attend']) && $body_chart_data['attend'] == 'Yes') echo 'selected'; ?>>Yes</option>
                          <option value="No" <?php if (isset($body_chart_data['attend']) && $body_chart_data['attend'] == 'No') echo 'selected'; ?>>No</option>
                          <option value="Sick or Away" <?php if (isset($body_chart_data['attend']) && $body_chart_data['attend'] == 'Sick or Away') echo 'selected'; ?>>Sick or Away</option>
                        </select></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>
      <section class="section4  analysisform <?php if (isset($body_chart_data['referal']) && $body_chart_data['referal'] == 'No' || !$body_chart_data['referal'] || $body_chart_data['referal'] == 'Info Only') {
                                                echo 'd-none';
                                              } else {
                                                echo 'pdf-capture';
                                              } ?>">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-4-1.jpg" alt="" />
          <div class="innerContainer examinationType">
            <h2>EXAMINATION TYPE: 20 MINUTE</h2>
            <div class="canvaContainer">
              <div class="innerCanva">
                <div class="grid-container">
                  <div class="grid-item item-1">

                    <canvas id="frontCanvas" width="300" height="300"></canvas>
                    <input name="mark_positions" type="hidden" id="markPositions" value="<?php if (isset($body_chart_data['mark_positions'])) echo htmlspecialchars($body_chart_data['mark_positions'], ENT_QUOTES, 'UTF-8');
                                                                                          else  echo ''; ?>">
                    <input name="frontCanvas" type="hidden" id="frontCanvas_point" value="<?php if (isset($body_chart_data['frontCanvas'])) echo $body_chart_data['frontCanvas'];
                                                                                          else echo ''; ?>">
                    <button type="button" class="removefrompdf" onclick="undoMark('frontCanvas')">Undo</button>
                  </div>
                  <div class="grid-item item-2">

                    <canvas id="backCanvas" width="300" height="300"></canvas>
                    <input name="backCanvas" type="hidden" id="backCanvas_point" value="<?php if (isset($body_chart_data['backCanvas'])) echo $body_chart_data['backCanvas'];
                                                                                        else echo ''; ?>">
                    <button type="button" class="removefrompdf" onclick="undoMark('backCanvas')">Undo</button>
                  </div>

                </div>
                <div class="grid-container">
                  <div class="grid-item item-3">

                    <canvas id="face1Canvas" width="300" height="300"></canvas>
                    <input name="face1Canvas" type="hidden" id="face1Canvas_point" value="<?php if (isset($body_chart_data['face1Canvas'])) echo $body_chart_data['face1Canvas'];
                                                                                          else echo ''; ?>">
                    <button type="button" class="removefrompdf" onclick="undoMark('face1Canvas')">Undo</button>
                  </div>
                  <div class="grid-item item-4">

                    <canvas id="face2Canvas" width="300" height="300"></canvas>
                    <input name="face2Canvas" type="hidden" id="face2Canvas_point" value="<?php if (isset($body_chart_data['face2Canvas'])) echo $body_chart_data['face2Canvas'];
                                                                                          else echo ''; ?>">
                    <button type="button" class="removefrompdf" onclick="undoMark('face2Canvas')">Undo</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>

      <section class="section41 skinCareSummary <?php if (isset($body_chart_data['referal']) && $body_chart_data['referal'] == 'No' || !$body_chart_data['referal'] || $body_chart_data['referal'] !== 'Info Only') {
                                                  echo 'd-none';
                                                } else {
                                                  echo 'pdf-capture';
                                                } ?>">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-3-1.jpg" alt="" />
          <div class="innerContainer mb-3">
            <h2 class="sectionTitle" style="text-align:center;">YOUR SKIN CHECK SUMMARY</h2>
            <div class="row" style="display:flex;flex-wrap:wrap;">
              <div class="col-md-4 mb-4" style="">
                <div class="bg_primary minGr">
                  <div class="innerGrid">
                    <p>
                      This summary has been provided to support your awareness and assist with future monitoring of your skin.
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-md-4 mb-4" style="">
                <div class="bg_primary minGr">
                  <div class="innerGrid imgLogo">
                    <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/skinCareSummaryLogo.jpg" alt="" />
                  </div>
                </div>
              </div>
              <div class="col-md-4 mb-4" style="">
                <div class="bg_primary minGr">
                  <div class="innerGrid">
                    <p>
                      Clinical images have been included where appropriate for reference and comparison over time.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="innerContainer mb-3">
            <h3 class="">LESION DETAILS</h3>
            <?php echo get_dropdown_with_field_map('Info:Details', 'Select Comments', 'lession_comments', $body_chart_data['populate_lession_comments']); ?>
            <div class="formInput">
              <textarea name="lession_comments" id="" rows="4" style="height: 0px;"><?php if (isset($body_chart_data['lession_comments'])) {
                                                                                      echo $body_chart_data['lession_comments'];
                                                                                    } ?></textarea>
            </div>
          </div>
          <div class="innerContainer mb-3">
            <input type="hidden" id="photo_file_url2" name="uploaded_file_url2" value="<?php if (isset($body_chart_data['uploaded_file_url2']) && $body_chart_data['uploaded_file_url2']) echo  $body_chart_data['uploaded_file_url2']; ?>">
            <div class="row" style="display:flex;flex-wrap:wrap;" id="preview2">
              <?php
              $uploaded_files2 = isset($body_chart_data['uploaded_file_url2']) ? $body_chart_data['uploaded_file_url2'] : '';
              if (!empty($uploaded_files2)) {
                $files2 = explode(',', $uploaded_files2);
                foreach ($files2 as $file) { ?>
                  <div class="col-md-3 mb-4" style="width:25%;">
                    <div class="bg_primary p_2">
                      <img class="imgFulCover" src="<?php echo $file; ?>" alt="">
                    </div>
                    <a href="#" class="remove_file2 removefrompdf">Remove</a>
                  </div>

                <?php }
              } else { ?>
                <div class="col-md-3 mb-4" style="width:25%;">
                  <div class="bg_primary p_2">
                    <img class="imgFulCover" src="https://placehold.co/200x200" alt="">
                  </div>
                </div>
                <div class="col-md-3 mb-4" style="width:25%;">
                  <div class="bg_primary p_2">
                    <img class="imgFulCover" src="https://placehold.co/200x200" alt="">
                  </div>
                </div>
                <div class="col-md-3 mb-4" style="width:25%;">
                  <div class="bg_primary p_2">
                    <img class="imgFulCover" src="https://placehold.co/200x200" alt="">
                  </div>
                </div>
                <div class="col-md-3 mb-4" style="width:25%;">
                  <div class="bg_primary p_2">
                    <img class="imgFulCover" src="https://placehold.co/200x200" alt="">
                  </div>
                </div>
              <?php } ?>

            </div>
            <div class="uploaderbtn text-center center">
              <button id="upload_file_button2" class="removefrompdf button button-primary">Upload File</button>
              <input type="file" id="image_file2" style="display: none;" accept=".jpg,.jpeg,.png,.JPG,.JPEG,.PNG" multiple />
              <p class="removefrompdf">Note:Please add 200x200 size of image. if need you can edit and resize after upload.</p>
            </div>

          </div>

          <div class="innerContainer mb-3">
            <h3 class="">PRACTITIONER NOTES</h3>
            <?php echo get_dropdown_with_field_map('Info:Notes', 'Select Notes', 'pra_comments', $body_chart_data['populate_pra_comments']); ?>
            <div class="formInput">
              <textarea name="pra_comments" id="" rows="4" style="height: 0px;"><?php if (isset($body_chart_data['pra_comments'])) {
                                                                                  echo $body_chart_data['pra_comments'];
                                                                                } ?></textarea>
            </div>
          </div>
          <div class="innerContainer mb-3">
            <h3 class="">COMMENTS SECTION:</h3>
            <?php echo get_dropdown_with_field_map('Info:Comments', 'Select Comments', 'info_comments', $body_chart_data['populate_info_comments']); ?>
            <div class="formInput">
              <textarea name="info_comments" id="" rows="4" style="height: 0px;"><?php if (isset($body_chart_data['info_comments'])) {
                                                                                    echo $body_chart_data['info_comments'];
                                                                                  } ?></textarea>
            </div>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>

      <section class="section5  <?php if (isset($body_chart_data['referal']) && $body_chart_data['referal'] == 'No' || !$body_chart_data['referal']  || $body_chart_data['referal'] == 'Info Only') echo 'd-none';
                                else echo 'pdf-capture'; ?>" id="detailAnalysis">
        <div class="container" id="refform">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-3-1.jpg" alt="" />
          <div class="innerContainer detailedAnalysis">
            <h2 class="sectionTitle">Detailed Analysis</h2>
            <?php //print_r($body_chart_data)
            ?>
            <div class="formInput">
              <div class="detailedAnalysisContainer">
                <div class="leftInputBox">
                  <div class="parts my-4 populatefield">
                    <div class="mb-2 da-flex align-items-end">
                      <p class="mb-0 text-nowrap">Location(s):</p>
                      <?php echo get_dropdown_with_field_map('Referral:Locations', 'Select a Location', 'localtion_1', $body_chart_data['populate_localtion_1']); ?>
                    </div>
                    <div class="mb-2 d-sflex align-items-start">
                      <p class="mb-0 mt-1 fw-bold me-2 mt-1 text-nowrap"></p>
                      <textarea class="pdfInput mb-0" name="localtion_1" id=""><?php if (isset($body_chart_data['localtion_1'])) {
                                                                                  echo $body_chart_data['localtion_1'];
                                                                                } ?></textarea>

                    </div>
                    <?php echo ai_improve_button('localtion_1'); ?>
                    <!--   <div class="mb-2 d-flex align-items-start">
                 <p class="mb-0 mt-1 fw-bold me-2 mt-1 text-nowrap"></p>
                <textarea class="pdfInput mb-0" type="text" name="localtion_2" id="" ><?php  //if(isset($body_chart_data['localtion_2'])){ echo $body_chart_data['localtion_2'];}
                                                                                      ?></textarea>
              </div>-->
                  </div>
                  <div class="parts my-4 populatefield">
                    <div class="mb-2 d-sflex align-items-start">
                      <p class="mb-0">
                        Clinical history and features:
                        <small>
                          <i>Colour, Shape, Border, Size, Surface, Symptoms </i>
                        </small>
                      </p>
                      <?php echo get_dropdown_with_field_map('Referral:History', 'Select a History', 'clinical_1', $body_chart_data['populate_clinical_1']); ?>
                    </div>
                    <div class="mb-2 d-flex align-items-start">
                      <p class="mb-0 mt-1 fw-bold me-2 mt-1 text-nowrap"></p>
                      <textarea class="pdfInput mb-0" type="text" name="clinical_1" id="">
                        <?php if (isset($body_chart_data['clinical_1'])) {
                          echo $body_chart_data['clinical_1'];
                        } ?></textarea>

                    </div>
                    <?php echo ai_improve_button('clinical_1'); ?>
                    <!--  <div class="mb-2 d-flex align-items-start">
                 <p class="mb-0 mt-1 fw-bold me-2 mt-1 text-nowrap"></p>
                <textarea  class="pdfInput mb-0" type="text" name="clinical_2" id="" ><?php // if(isset($body_chart_data['clinical_2'])){ echo $body_chart_data['clinical_2'];}
                                                                                      ?></textarea>
              </div>-->
                  </div>
                  <div class="parts my-4 populatefield">
                    <div class="mb-2 sd-flex align-items-end">
                      <p class="mb-0">
                        Dermatoscopic features:
                        <small>
                          <i>
                            Pigmented or Non-Pigmented, Pattern(s), Colour(s) Clues
                            to Malignancy
                          </i>
                        </small>
                      </p>
                      <?php echo get_dropdown_with_field_map('Referral:Features', 'Select a Features', 'dermatoscopic_1', $body_chart_data['populate_dermatoscopic_1']); ?>
                    </div>
                    <div class="mb-2 d-flex align-items-start">
                      <p class="mb-0 mt-1 fw-bold me-2 mt-1 text-nowrap"></p>
                      <textarea class="pdfInput mb-0" type="text" name="dermatoscopic_1" id=""><?php if (isset($body_chart_data['dermatoscopic_1'])) {
                                                                                                  echo $body_chart_data['dermatoscopic_1'];
                                                                                                } ?></textarea>
                    </div>
                    <?php echo ai_improve_button('dermatoscopic_1'); ?>
                    <!--   <div class="mb-2 d-flex align-items-start">
                 <p class="mb-0 mt-1 fw-bold me-2 mt-1 text-nowrap"></p>
                <textarea class="pdfInput mb-0" type="text" name="dermatoscopic_2" id=""><?php // if(isset($body_chart_data['dermatoscopic_2'])){ echo $body_chart_data['dermatoscopic_2'];}
                                                                                          ?></textarea>
              </div>-->
                  </div>
                  <!-- <p>Location</p>
                    <textarea name="location_2" id="" rows="2"> <?php //if(isset($body_chart_data['location_2'])) echo $body_chart_data['location_2']; else echo '';
                                                                ?></textarea>
                    <p>Notes</p>
                    <textarea name="note" id="" rows="5"><?php //if(isset($body_chart_data['note'])) echo $body_chart_data['note']; else echo '';
                                                          ?></textarea>-->
                </div>
                <input type="hidden" id="photo_file_url" name="uploaded_file_url" value="<?php if (isset($body_chart_data['uploaded_file_url']) && $body_chart_data['uploaded_file_url']) echo  $body_chart_data['uploaded_file_url']; ?>">
                <div class="detailedAnalysisImg text-center">
                  <p>Photo</p>
                  <div id="file_preview" style="display:flex;flex-wrap:wrap;align-content:center;">
                    <?php
                    $uploaded_files = isset($body_chart_data['uploaded_file_url']) ? $body_chart_data['uploaded_file_url'] : '';
                    if (!empty($uploaded_files)) {
                      $files = explode(',', $uploaded_files);
                      foreach ($files as $file) {
                        echo '<div style="width:calc(50% - 15px);margin:7.5px;height:300px;max-height:260px;"><img style="width:100%;object-fit:contain;height:100%;max-height:300px;" src="' . esc_url($file) . '"><a href="#" class="remove_file removefrompdf">Remove</a></div>';
                      }
                    } else {
                      echo '<img class="w-100" src="https://placehold.co/300x300"
                      alt=""/>';
                    }
                    ?>
                    <?php /*if(isset($body_chart_data['uploaded_file_url']) && $body_chart_data['uploaded_file_url']) echo '  <img class="w-100" src="'.$body_chart_data['uploaded_file_url'].'"
                      alt=""/>'; else echo '  <img class="w-100" src="https://placehold.co/300x300"
                      alt=""/>';*/ ?>

                  </div>
                  <button id="upload_file_button" class="removefrompdf button button-primary">Upload File</button>
                  <input type="file" id="image_file" style="display: none;" accept=".jpg,.jpeg,.png,.JPG,.JPEG,.PNG" multiple />
                  <p class="removefrompdf">Note:Please add 300x300 size of image. if need you can edit and resize after upload.</p>

                </div>
              </div>
              <!--    <p>Recommendations</p>
                <textarea name="recommendations" id="" rows="6"><?php //if(isset($body_chart_data['recommendations'])) echo $body_chart_data['recommendations']; else echo '';
                                                                ?></textarea>-->

              <div class="mb-2 da-flex align-items-end">
                <p>Comments</p>
                <?php echo get_dropdown_with_field_map('Referral:Comments', 'Select Comments', 'comments', $body_chart_data['populate_comments']); ?>
              </div>
              <textarea name="comments" id="" rows="3"><?php if (isset($body_chart_data['comments'])) echo $body_chart_data['comments'];
                                                        else echo ''; ?></textarea>
            </div>
            <?php echo ai_improve_button('comments'); ?>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>
      <section class="section6 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-3-1.jpg" alt="" />
          <!-- <img class="w-100" src="<?php //echo WPAMELIA_ADDON_PLUGIN_URL
                                        ?>/admin/images/page-6.jpg" alt="" /> -->
          <h2 class="sectionTitle text-center">
            THE SUN, YOUR SKIN AND SKIN CANCER <br>
            - WHAT YOU NEED TO KNOW
          </h2>
          <div class="row" style="display:flex;flex-wrap:wrap;">
            <div class="col-6" style="width:50%">
              <h3 class="text-uppercase mb-3">PREVENTION STRATEGIES</h3>
              <p>
                Nearly all skin cancers are directly related to exposure to ultraviolet (UV) radiation – which can be prevented! Using sun protection consistently from an early age is the strongest defense against developing skin cancer. <br><br>
                When you protect your skin from the sun’s UV radiation, you reduce your risk of developing skin cancer.<br><br>
                There is no single method of sun defense that can protect you perfectly, however implementing the below strategies provides you with the best possible chance
              </p>
              <div class="listItemWithNumber listItems">
                <div class="d-flex item">
                  <strong>1</strong>
                  <p>
                    Avoid direct UV sunlight, especially between 10am and 4pm
                  </p>
                </div>
                <div class="d-flex item">
                  <strong>2</strong>
                  <p>
                    Avoid getting sunburned
                  </p>
                </div>
                <div class="d-flex item">
                  <strong>3</strong>
                  <p>
                    Avoid tanning
                  </p>
                </div>
                <div class="d-flex item">
                  <strong>4</strong>
                  <p>
                    Cover up with clothing, including broad-brimmed hat – the more skin you cover, the better.
                  </p>
                </div>
                <div class="d-flex item">
                  <strong>5</strong>
                  <p>
                    Use a broad-spectrum (UVA/UVB) sunscreen - Apply 2 tablespoons of sunscreen to your entire body 30 minutes before going outside. Reapply every two hours or after swimming or excessive sweating – Don’t forget to apply it to your hands, especially after washing them.
                  </p>
                </div>
                <div class="d-flex item">
                  <strong>6</strong>
                  <p>
                    Wear sunglasses that block UVA and UVB rays
                  </p>
                </div>
                <div class="d-flex item">
                  <strong>7</strong>
                  <p>
                    Examine your skin head to toe (see below for what to look for)
                  </p>
                </div>
              </div>
            </div>
            <div class="col-6" style="width:50%">
              <h3 class="text-uppercase mb-3">PREVENTION STRATEGIES</h3>
              <p>
                When caught and treated early, skin cancers are highly curable. Skin cancers can appear in many shapes and sizes, it is important to know the warning signs associated with basal cell carcinoma (BCC), squamous cell carcinoma (SCC), melanoma, Markel cell carcinoma (MCC) and precancer actinic keratosis (AK). <br><br>
                Look for anything NEW, CHANGING or UNUSUAL and get it checked by a dermatologist right away. Try to remember the ABCDE rule for skin cancer when doing a self-exam. Consider the following signs of skin cancer:
              </p>
              <div class="listItemWithIcon listItems">
                <div class="d-flex item">
                  <div class="iconBoxSm"></div>
                  <strong>Asymmetry</strong>
                  <p>
                    - A spot or mole on your skin with an unusual shape, or two parts that don’t look the same
                  </p>
                </div>
                <div class="d-flex item">
                  <div class="iconBoxSm"></div>
                  <strong>Border</strong>
                  <p>
                    - A jagged or uneven border
                  </p>
                </div>
                <div class="d-flex item">
                  <div class="iconBoxSm"></div>
                  <strong>Colour</strong>
                  <p>
                    - An uneven colour
                  </p>
                </div>
                <div class="d-flex item">
                  <div class="iconBoxSm"></div>
                  <strong>Diameter</strong>
                  <p>
                    - A mole or spot that is larger than a pea
                  </p>
                </div>
                <div class="d-flex item">
                  <div class="iconBoxSm"></div>
                  <strong>Evolving</strong>
                  <p>
                    - A mole or spot that has changed within the past couple of weeks or months
                  </p>
                </div>
              </div>
              <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/abcdes-of.jpg" alt="" />
            </div>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>
      <section class="section7 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-3-1.jpg" alt="" />
          <div class="innerContainer detailedAnalysis">
            <h2 class="sectionTitle text-center">HOW TO PERFORM A SELF-EXAMINATION</h2>
            <div class="row" style="display:flex;flex-wrap:wrap;">
              <div class="col-md-3 text-center mb-4" style="width:25%;">
                <img class="w-75" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/grid_Screenshot_1.png" alt="" />
                <h3 class="text-uppercase text-center my-3">EXAMINE YOUR FACE</h3>
                <p>
                  Especially your nose, lips, mouth and ears – front and back. Use one or both mirror to get a clear view.
                </p>
              </div>
              <div class="col-md-3 text-center mb-4" style="width:25%;">
                <img class="w-75" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/grid_Screenshot_2.png" alt="" />
                <h3 class="text-uppercase text-center my-3">INSPECT YOUR SCALP</h3>
                <p>
                  Thoroughly inspect your scalp, using a blow-dryer and mirror to expose each section to view. Get a friend or family member to help, if you can.
                </p>
              </div>
              <div class="col-md-3 text-center mb-4" style="width:25%;">
                <img class="w-75" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/grid_Screenshot_3.png" alt="" />
                <h3 class="text-uppercase text-center my-3">CHECK YOUR HANDS</h3>
                <p>
                  Palms and backs, between the fingers and under the fingernails. Continue up the wrists to examine both the front and back of your forearms.
                </p>
              </div>
              <div class="col-md-3 text-center mb-4" style="width:25%;">
                <img class="w-75" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/grid_Screenshot_4.png" alt="" />
                <h3 class="text-uppercase text-center my-3">SCAN YOUR ARMS</h3>
                <p>
                  Standing in front of a full-length mirror, begin at the elbows and scan all sides of your upper arms. Don’t forget he underarms.
                </p>
              </div>
              <div class="col-md-3 text-center mb-4" style="width:25%;">
                <img class="w-75" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/grid_Screenshot_5.png" alt="" />
                <h3 class="text-uppercase text-center my-3">INSPECT YOUR TORSO</h3>
                <p>
                  Next, focus on the neck, chest and torso. Lift breasts to view the undersides.
                </p>
              </div>
              <div class="col-md-3 text-center mb-4" style="width:25%;">
                <img class="w-75" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/grid_Screenshot_6.png" alt="" />
                <h3 class="text-uppercase text-center my-3">SCAN YOUR UPPER BACK</h3>
                <p>
                  With your back to the full-length mirror, use the hand mirror to inspect the back of your neck, shoulders, upper ack and any part of the back of your arms you could not view in step 4.
                </p>
              </div>
              <div class="col-md-3 text-center mb-4" style="width:25%;">
                <img class="w-75" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/grid_Screenshot_7.png" alt="" />
                <h3 class="text-uppercase text-center my-3">SCAN YOUR LOWER BACK</h3>
                <p>
                  Still using both mirrors, can your lower back, buttocks and backs of both legs.
                </p>
              </div>
              <div class="col-md-3 text-center mb-4" style="width:25%;">
                <img class="w-75" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/grid_Screenshot_8.png" alt="" />
                <h3 class="text-uppercase text-center my-3">INSPECT YOUR LEGS</h3>
                <p>
                  Sit down; prop each leg in turn on the other stool or chair. Use the hand mirror to examine the genitals. Check the front and sides of both legs, thigh to shin. Then, finish with ankles and feet, including soles, toes and nails (without polish)
                </p>
              </div>
            </div>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>
      <section class="section8 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-3-1.jpg" alt="" />
          <div class="innerContainer">
            <div class="risk-gauge">
              <div class="gauge" id="gauge" style="width:450px;height:250px;margin:0 auto;">
                <div class="noNeed dsfds">
                  <div class="gauge-arc"></div>
                  <div class="needle" id="needle"></div>
                  <div class="center-dot" id="center-dot"></div>
                  <div class="labels">
                    <span class="label low">Low </span>
                    <span class="label medium">Medium</span>
                    <span class="label high">High</span>
                  </div>
                </div>

                <!-- <img class="w-100 field" src="<?php //echo WPAMELIA_ADDON_PLUGIN_URL
                                                    ?>/admin/images/bgField.png" alt="" />
                <img class="icon" id="iconRotate" src="<?php //echo WPAMELIA_ADDON_PLUGIN_URL
                                                        ?>/admin/images/iconRotate.png" alt="" /> -->

                <img class="w-100 field" style="width:100%;" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/bgField.png" alt="" />
                <img class="icon" id="iconRotate" style="position:unset;width:70px;height:155px;background-size:contain;background-repeat:no-repeat;background-position:top;transform-origin:bottom;margin-top:-170px;object-fit:contain;" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/iconRotate.png" alt="" />
              </div>
              <div class="risk-level" id="risk-level">Risk Level: <?php if ($highRisk == 'Yes') {
                                                                    echo 'High';
                                                                  } else { ?> Low <?php } ?></div>
              <div class="controls">
                <button class="removefrompdf" type="button" id="decrease">Minus</button>
                <input type="hidden" name="level_risk_value" id="level_risk_value">
                <button class="removefrompdf" type="button" id="increase">Plus</button>
                <input type="hidden" name="level_risk" id="level_risk">

                <!-- <button onclick="setRisk('high')" class="removefrompdf" type="button">High Risk</button>-->
              </div>
            </div>
          </div>
          <h2 class="sectionTitle text-center mt-5">
            BE SURE TO KNOW THE FACTORS THAT INCREASE <br>
            OR DECREASE YOUR SKIN CANCER RISK
          </h2>

          <table class="table table-bordered">
            <thead>
              <tr>
                <th colspan="2" class="text-uppercase text-center bg_brown">
                  THE FOLLOWING FACTORS INCREASE YOUR SKIN CANCER RISK
                </th>
                <th colspan="2" class="text-uppercase text-center bg_primary">
                  THE FOLLOWING CAN HELP DECREAS YOUR SKIN CANCER RISK
                </th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/x_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Fair Skin
                </td>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/check_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Daily use of broad spectrum sunscreen with an SPF 30+ or higher
                </td>
              </tr>
              <tr>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/x_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Sunburns
                </td>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/check_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Use of sun protective clothing, UV blocking sunglasses and wide-brimmed hats
                </td>
              </tr>
              <tr>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/x_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Unprotected exposure to UVA & UVB rays
                </td>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/check_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Seeking shade whenever possible
                </td>
              </tr>
              <tr>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/x_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Genetics
                </td>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/check_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Protective window film in your car and home
                </td>
              </tr>
              <tr>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/x_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Atypical moles
                </td>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/check_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Annual skin checs
                </td>
              </tr>
              <tr>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/x_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Organ transplant
                </td>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/check_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Monthly self exams
                </td>
              </tr>
              <tr>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/x_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Red hair
                </td>
                <td class="align-middle">
                  <div style="width:max-content;">
                    <img style="height:40px;width:40px;object-fit:contain;" class="iconXCheck" height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/check_mark.png" alt="" />
                  </div>
                </td>
                <td class="align-middle">
                  Reapplication of sunscreen every 2 hours while outdoors
                </td>
              </tr>
            </tbody>
          </table>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>
      <section class="section9 pdf-capture" style="padding-bottom: 20px;">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-9-1.jpg" alt="" />
          <div>
            <h2 class="sectionTitle mb-3">
              RESOURCES
            </h2>
            <div class="d-flex mb-4">
              <div class="me-3">
                <img height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/designIcon.png" alt="" />
              </div>
              <div>
                <h3 class="text-uppercase">PREVENT SKIN CANCER & SUNBURN THIS SUMMER</h3>
                <p class="mb-0">https://www.sunsmart.com.au/</p>
              </div>
            </div>
            <div class="d-flex mb-4">
              <div class="me-3">
                <img height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/designIcon.png" alt="" />
              </div>
              <div>
                <h3 class="text-uppercase">SUNSMART GLOBAL UV APP</h3>
                <p class="mb-0">https://www.sunsmart.com.au/resources/sunsmart-app</p>
              </div>
            </div>
            <div class="d-flex mb-4">
              <div class="me-3">
                <img height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/designIcon.png" alt="" />
              </div>
              <div>
                <h3 class="text-uppercase">OUTDOOR-WORK-AND-SUN-PROTECTION.</h3>
                <p class="mb-0">https://cancerwa.asn.au/wp-content/uploads/2022/07/Outdoor-work-and-sun-protection.pdf</p>
              </div>
            </div>
            <div class="d-flex mb-4">
              <div class="me-3">
                <img height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/designIcon.png" alt="" />
              </div>
              <div>
                <h3 class="text-uppercase">SKIN CANCER | CAUSES, SYMPTOMS & TREATMENTS</h3>
                <p class="mb-0">https://www.cancer.org.au/cancer-information/types-of-cancer/skin-cancer</p>
              </div>
            </div>
            <div class="d-flex mb-4">
              <div class="me-3">
                <img height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/designIcon.png" alt="" />
              </div>
              <div>
                <h3 class="text-uppercase">WESTERN AUSTRALIAN KIRKBRIDE MELANOMA ADVISORY SERVICE (WAKMAS)</h3>
                <p class="mb-0">https://perkins.org.au/research/labs/centres-facilities/western-australian-kirkbride-melanoma-a</p>
              </div>
            </div>
            <div class="d-flex mb-4">
              <div class="me-3">
                <img height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/designIcon.png" alt="" />
              </div>
              <div>
                <h3 class="text-uppercase">INFORMATION FOR PATIENTS</h3>
                <p class="mb-0">https://wakmas.org.au/information-for-patients/</p>
              </div>
            </div>
            <div class="d-flex mb-4">
              <div class="me-3">
                <img height="40" width="40" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/designIcon.png" alt="" />
              </div>
              <div>
                <h3 class="text-uppercase">MELANOMA</h3>
                <p class="mb-0">https://melanoma.org.au/</p>
              </div>
            </div>
          </div>
          <div class="my-4">
            <h3 class="text-uppercase">DISCLAIMER</h3>
            <p class="mb-0">
              Skin Chx complies with the Australian Government privacy legislation. Therefore, all information collected from employee health assessments is kept private and confidential. Any reporting of data provided to an employee’s company is de-identified as per Skin Chx Privacy policy.
            </p>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-9-2.jpg" alt="" />
        </div>
      </section>
    </main>
    <div class="amlia_form_btns sticky-bottom bg-white py-2">
      <button class="btn btn-primary" name="submit" type="submit" id="save_body_chart">Save</button>
      <button class="btn btn-primary" id="pdf_generator" type="button" id="generate_pdf">Pdf Generate</button>


      <!--<button class="btn btn-primary"  id="save_send_email" type="button" >Save & Send Report</button>-->

      <button class="btn btn-primary" id="openModalBtn" type="button">Send Report</button>
      <a class="btn btn-primary <?php if (isset($body_chart_data['referal']) && $body_chart_data['referal'] == 'No' || !$body_chart_data['referal'] || $body_chart_data['referal'] == 'Info Only') echo 'd-none'; ?>" id="ref_btn" href="<?php echo admin_url('admin.php?page=wpamelia-bodychart&id=' . $id . '&referal=true') ?>">Referal Report</a>

    </div>
    <input type="hidden" name="appoinment_id" id="appoinment_id" value="<?php echo $id; ?>">
    <div id="alert-message" style="display: none; padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; margin-top: 10px;text-align: center;">
      <span id="notification-message"></span>
    </div>
  </form>
<?php } ?>
<div id="emailModal" class="modal">
  <div class="modal-content">
    <button type="button" class="btn-close modal-close-icon" id="closeModalBtn" data-bs-dismiss="modal" aria-label="Close">&times;</button>
    <h2>Add Email Addresses</h2>
    <input class="w-100" type="email" id="emailInput" placeholder="Enter email" />
    <button class="w-100 mx-0 btn-success btn" id="addEmailBtn">Add Email</button>

    <ul class="email-list w-100" id="emailList">
      <?php if ($email) { ?>
        <li><?php echo $email; ?><button data-email="<?php echo $email; ?>" class="remove-icon" title="Remove Email" fdprocessedid="n3c7h59">×</button></li>
      <?php } ?>
    </ul>
    <button class="btn btn-primary" id="save_send_email" type="button">Save & Send Report</button>
  </div>
</div>
<style>
  .select2.select2-container {
    width: 100% !important;
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
    max-width: 450px !important;
  }
  
  .generating .risk-gauge {
    max-width: 500px !important;
    margin: 30px auto !important;
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
      white-space: normal !important;
      display: block !important;
    }
    
    .sticky-bottom {
      position: relative !important;
      left: 0 !important;
      right: 0 !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    
    main, .pdfHtml, .pdf-capture, .container {
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
      box-sizing: border-box !important;
    }
    
    .modal-content input,
    .modal-content button {
      width: 75% !important;
      box-sizing: border-box !important;
      display: block !important;
      margin: 0 auto 8px auto !important;
    }
    
    .section_heading {
      font-size: 16px !important;
      padding: 10px !important;
    }
  }
</style>
<script>
  function referal_option_drop(that) {
    var value = that.value;
    if (value == 'No' || !value) {
      jQuery('#ref_btn').addClass('d-none');
      jQuery('.analysisform').addClass('d-none');
      jQuery('#detailAnalysis').addClass('d-none');

      jQuery('.analysisform').removeClass('pdf-capture');
      jQuery('.skinCareSummary').addClass('d-none');
      jQuery('.skinCareSummary').removeClass('pdf-capture');

    } else {



      if (value == 'Info Only') {
        jQuery('#detailAnalysis').addClass('d-none');
        jQuery('.analysisform').addClass('d-none');
        jQuery('#detailAnalysis').removeClass('pdf-capture');
        jQuery('.skinCareSummary').removeClass('d-none');

        jQuery('.skinCareSummary').addClass('pdf-capture');
        jQuery('#ref_btn').addClass('d-none');
      } else {
        jQuery('#ref_btn').removeClass('d-none');
        jQuery('#detailAnalysis').removeClass('d-none');
        jQuery('#detailAnalysis').addClass('pdf-capture');


        jQuery('.analysisform').removeClass('d-none');
        jQuery('.analysisform').addClass('pdf-capture');
        jQuery('.skinCareSummary').addClass('d-none');
        jQuery('.skinCareSummary').removeClass('pdf-capture');
      }

    }
  }
  jQuery(document).ready(function($) {
    $('.select2drop').select2();
    $('.select2drop').each(function() {
      var $this = $(this);
      var placeholder = $this.find('option[value="0"]').text(); // Get the text of the first option

      $this.select2({
        placeholder: placeholder, // Set the dynamic placeholder
        allowClear: true // Allow clearing the selection
      });
    });
    $('#upload_file_button').on('click', function(e) {
      e.preventDefault();
      $('#image_file').trigger('click'); // Trigger the hidden file input
    });
    $('#image_file').on('change', function() {
      jQuery('#ajax-loader').show();
      var fileInput = this;
      var files = fileInput.files; // Get the selected files

      if (files.length > 0) {
        var formData = new FormData();

        // Loop through all selected files and append them to FormData
        $.each(files, function(i, file) {
          formData.append('image_file[]', file); // Use array for multiple files
        });

        formData.append('action', 'amelia_custom_image_upload_multiple'); // Set custom action

        // Show loading message
        $('#uploading_message').show();

        // Perform AJAX request
        $.ajax({
          url: "<?php echo admin_url('admin-ajax.php'); ?>", // WordPress AJAX URL
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          success: function(response) {
            jQuery('#ajax-loader').hide();
            // $('#uploading_message').hide();

            // Handle success response
            if (response.success) {
              var previewHtml = '';
              // Iterate over uploaded images and generate previews

              var existingFiles = $('#photo_file_url').val();
              var newFiles = response.data.urls.join(',');
              var finalFiles = existingFiles ? existingFiles + ',' + newFiles : newFiles;
              /*    finalFiles.split(',').forEach(function(url) {
                     $('#file_preview').append('<div style="width:calc(50% - 15px);margin:7.5px;height:260px;max-height:260px;"><img style="width:100%;object-fit:contain;height:100%;max-height:260px;" src="'+url+'" width="100"><a href="#" class="remove_file removefrompdf">Remove</a></div>');
                 });*/
              console.log(finalFiles);
              console.log(finalFiles.split(','));
              finalFiles.split(',').forEach(function(url) {
                previewHtml += '<div style="width:calc(50% - 15px);margin:7.5px;height:260px;max-height:260px;">';
                previewHtml += '<img src="' + url + '" alt="Preview" style="width:100%;object-fit:contain;height:100%;max-height:260px;">';
                previewHtml += '<a href="#" class="remove_file removefrompdf">Remove</a>';
                previewHtml += '</div>';
                
                // Auto-save to historical photos database
                if (typeof window.savePhotoToHistory === 'function') {
                  window.savePhotoToHistory(url, 'frontCanvas', null, null);
                }
              });
              $('#file_preview').html(previewHtml);

              // Optionally, store URLs in hidden input field or handle accordingly
              $('#photo_file_url').val(finalFiles);

            } else {
              alert('Image upload failed. Please try again.');
            }
          },
          error: function() {
            alert('Error uploading image.');
            // $('#uploading_message').hide();
            jQuery('#ajax-loader').hide();
          }
        });
      }
    });
    $('#upload_file_button2').on('click', function(e) {
      e.preventDefault();
      $('#image_file2').trigger('click'); // Trigger the hidden file input
    });
    $('#image_file2').on('change', function() {
      jQuery('#ajax-loader').show();
      var fileInput = this;
      var files = fileInput.files; // Get the selected files

      if (files.length > 0) {
        var formData = new FormData();

        // Loop through all selected files and append them to FormData
        $.each(files, function(i, file) {
          formData.append('image_file2[]', file); // Use array for multiple files
        });
        formData.append('info_file', 1);
        formData.append('action', 'amelia_custom_image_upload_multiple'); // Set custom action

        // Show loading message
        $('#uploading_message').show();

        // Perform AJAX request
        $.ajax({
          url: "<?php echo admin_url('admin-ajax.php'); ?>", // WordPress AJAX URL
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          success: function(response) {
            jQuery('#ajax-loader').hide();
            // $('#uploading_message').hide();

            // Handle success response
            if (response.success) {
              var previewHtml = '';
              // Iterate over uploaded images and generate previews

              var existingFiles = $('#photo_file_url2').val();
              var newFiles = response.data.urls.join(',');
              var finalFiles = existingFiles ? existingFiles + ',' + newFiles : newFiles;

              console.log(finalFiles);
              console.log(finalFiles.split(','));
              finalFiles.split(',').forEach(function(url) {

                previewHtml += `    <div class="col-md-3 mb-4" style="width:25%;">
                        <div class="bg_primary p_2">
                            <img class="imgFulCover" src="` + url + `" alt="">
                        </div>
                        <a href="#" class="remove_file2 removefrompdf">Remove</a></div>`;
                // previewHtml += '';
                
                // Auto-save to historical photos database
                if (typeof window.savePhotoToHistory === 'function') {
                  window.savePhotoToHistory(url, 'frontCanvas', null, null);
                }
              });
              $('#preview2').html(previewHtml);

              // Optionally, store URLs in hidden input field or handle accordingly
              $('#photo_file_url2').val(finalFiles);

            } else {
              alert('Image upload failed. Please try again.');
            }
          },
          error: function() {
            alert('Error uploading image.');
            // $('#uploading_message').hide();
            jQuery('#ajax-loader').hide();
          }
        });
      }
    });
    let file_frame;

    $('#upload_file_button_old').on('click', function(e) {
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
        multiple: true // Only select one file
      });

      // On file selection, update the input field and preview
      file_frame.on('select', function() {
        //const attachment = file_frame.state().get('selection').first().toJSON();
        const attachments = file_frame.state().get('selection').map(function(attachment) {
          attachment = attachment.toJSON();
          return attachment.url;
        });
        var existingFiles = $('#photo_file_url').val();
        var newFiles = attachments.join(',');
        var finalFiles = existingFiles ? existingFiles + ',' + newFiles : newFiles;
        //$('#uploaded_file_url').val(finalFiles);
        $('#photo_file_url').val(finalFiles);
        $('#file_preview').empty();
        finalFiles.split(',').forEach(function(url) {
          $('#file_preview').append('<div style="width:calc(50% - 15px);margin:7.5px;height:260px;max-height:260px;"><img style="width:100%;object-fit:contain;height:100%;max-height:260px;" src="' + url + '" width="100"><a href="#" class="remove_file removefrompdf">Remove</a></div>');
        });
      });

      // Open the uploader dialog
      file_frame.open();
    });
    $(document).on('click', '.remove_file', function(e) {
      e.preventDefault();
      $(this).parent().remove();
      var updatedFiles = [];
      $('#file_preview img').each(function() {
        updatedFiles.push($(this).attr('src'));
      });
      $('#photo_file_url').val(updatedFiles.join(','));
    });
    $(document).on('click', '.remove_file2', function(e) {
      e.preventDefault();
      $(this).parent().remove();
      var updatedFiles = [];
      $('#preview2 img').each(function() {
        updatedFiles.push($(this).attr('src'));
      });
      $('#photo_file_url2').val(updatedFiles.join(','));
    });
  });
</script>
<script>
  const textareas = document.getElementsByTagName('textarea');

  Array.from(textareas).forEach((textarea) => {
    textarea.addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = this.scrollHeight + 'px';
    });
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
  });
</script>

<script>
  var level = '<?php echo $level_risk; ?>';
  var rotate_value = '<?php echo $level_risk_value; ?>';
  setRisk(level, rotate_value);
  const images = {
    frontCanvas: '<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/front-part.png',

    backCanvas: '<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/back-part.png',
    face1Canvas: '<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/view-face1.png',
    face2Canvas: '<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/view-face2.png'
  };

  const markPositionsField = document.getElementById('markPositions');
  const marks = {
    frontCanvas: [],
    face1Canvas: [],
    face2Canvas: [],
    backCanvas: []
  };
  
  // Expose globally for the marker photo modal
  window.bodyChartMarks = marks;
  window.bodyChartImages = images;

  Object.keys(images).forEach(canvasId => {
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext('2d');
    const img = new Image();
    img.src = images[canvasId];
    img.onload = () => {
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    };

    //
    canvas.addEventListener('click', (event) => {
      const rect = canvas.getBoundingClientRect();
      const x = event.clientX - rect.left;
      const y = event.clientY - rect.top;
      marks[canvasId].push({
        x,
        y
      });
      drawMarks(canvasId);

      /*ctx.beginPath();
      ctx.arc(x, y, 8, 0, 2 * Math.PI);
      ctx.fillStyle = 'red';
      ctx.fill();
      ctx.stroke();*/
      
      // NEW (v9.6.9): Open "Attach Photo" modal for this marker
      if (typeof window.openMarkerPhotoModal === 'function') {
        window.openMarkerPhotoModal(canvasId, x, y, marks[canvasId].length - 1);
      }
    });
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
        // Red dot
        ctx.beginPath();
        ctx.arc(mark.x, mark.y, 8, 0, 2 * Math.PI);
        ctx.fillStyle = 'red';
        ctx.fill();
        ctx.stroke();
        
        // Photo indicator (small green camera badge)
        if (mark.photoId || mark.photoUrl) {
          ctx.beginPath();
          ctx.arc(mark.x + 9, mark.y - 9, 5, 0, 2 * Math.PI);
          ctx.fillStyle = '#28a745';
          ctx.fill();
          ctx.strokeStyle = '#fff';
          ctx.lineWidth = 1.5;
          ctx.stroke();
          ctx.lineWidth = 1;
          ctx.strokeStyle = '#000';
        }
      });
    };
    jQuery('#markPositions').val(JSON.stringify(marks));
  }
  window.redrawBodyChartMarks = drawMarks;
  if (markPositionsField.value) {
    var dataFromDB = '<?php echo isset($body_chart_data['mark_positions']) ? $body_chart_data['mark_positions'] : ''; ?>';
    const cleanData = dataFromDB.replace(/<[^>]*>/g, '');
    //alert(cleanData);
    const data = JSON.parse(cleanData || '{}');
    console.log(marks);
    
    // Populate the marks object from loaded data (preserve any photoId/photoUrl if saved on the mark)
    Object.keys(marks).forEach(function(canvasId) {
      if (data[canvasId] && Array.isArray(data[canvasId])) {
        marks[canvasId] = data[canvasId].map(function(m) {
          return {
            x: m.x,
            y: m.y,
            photoId: m.photoId || null,
            photoUrl: m.photoUrl || null,
            lesionId: m.lesionId || null
          };
        });
      }
    });
    
    // After canvas images load, redraw using drawMarks (which adds camera badges for photos)
    setTimeout(function() {
      Object.keys(marks).forEach(function(canvasId) {
        if (marks[canvasId].length > 0) {
          drawMarks(canvasId);
        }
      });
      
      // Also cross-reference with the patient_photos DB — enrich loaded marks that
      // were saved before the photoId was tracked (older body charts)
      var appointmentId = jQuery('#appoinment_id').val();
      if (appointmentId && typeof ajax !== 'undefined') {
        jQuery.ajax({
          url: ajax.url,
          type: 'POST',
          data: {
            action: 'amelia_get_marker_photos',
            nonce: ajax.nonce,
            appointment_id: appointmentId
          },
          success: function(resp) {
            if (!resp || !resp.success || !resp.data.photos) return;
            var enriched = false;
            resp.data.photos.forEach(function(photo) {
              var canvasMarks = marks[photo.body_location];
              if (!canvasMarks) return;
              // Find a nearby mark without a photoId and attach
              for (var i = 0; i < canvasMarks.length; i++) {
                var m = canvasMarks[i];
                if (!m.photoId && Math.abs(m.x - photo.marker_x) < 12 && Math.abs(m.y - photo.marker_y) < 12) {
                  m.photoId = photo.id;
                  m.photoUrl = photo.file_url;
                  m.lesionId = photo.lesion_id;
                  enriched = true;
                  break;
                }
              }
            });
            if (enriched) {
              Object.keys(marks).forEach(function(c) { if (marks[c].length > 0) drawMarks(c); });
            }
          }
        });
      }
    }, 1500);
  }

  function undoMark(canvasId) {
    if (marks[canvasId].length > 0) {
      var removed = marks[canvasId].pop();
      drawMarks(canvasId);
      
      // If the removed mark had an attached photo, also delete it from the patient_photos DB + file
      if (removed && removed.photoId) {
        jQuery.ajax({
          url: (typeof ajax !== 'undefined' && ajax.url) ? ajax.url : ajaxurl,
          type: 'POST',
          data: {
            action: 'amelia_delete_marker_photo',
            nonce: (typeof ajax !== 'undefined' && ajax.nonce) ? ajax.nonce : '',
            photo_id: removed.photoId
          }
        });
      }
    }
  }

  function loadMarks() {

    marks.forEach(mark => {
      drawMark(mark.x, mark.y);
    });
  }

  //loadMarks(); //
  //
  /*  function saveImage(canvasId) {
        const canvas = document.getElementById(canvasId);
        const dataURL = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = `${canvasId}_marked.png`;
        link.href = dataURL;
        link.click();
    }*/

  function setRisk(level, rotateval) {
    const needle = document.getElementById('iconRotate');
    const centerDot = document.getElementById('center-dot');
    const riskLevel = document.getElementById('risk-level');
    console.log(level);
    console.log(rotateval);
    jQuery('#level_risk').val(level);
    jQuery('#level_risk_value').val(rotateval);

    switch (level) {
      case 'low':
        needle.style.transform = 'rotate(' + rotateval + 'deg)'; // Needle Points Left
        //centerDot.style.transform = 'translate(-125px, 0)'; // Move Center Dot Left
        riskLevel.textContent = 'Risk Level: Low';
        break;
      case 'medium':
        needle.style.transform = 'rotate(' + rotateval + 'deg)'; // Needle Points Top
        //centerDot.style.transform = 'translate(0, 0)'; // Center Dot Reset
        riskLevel.textContent = 'Risk Level: Medium';
        break;
      case 'high':
        needle.style.transform = 'rotate(' + rotateval + 'deg)'; // Needle Points Right
        //centerDot.style.transform = 'translate(125px, 0)'; // Move Center Dot Right
        riskLevel.textContent = 'Risk Level: High';
        break;
      default:
        console.warn('Invalid risk level');
    }
  }
  //const highrisk=false;
  function checkriskValues() {
    const dropdowns = document.querySelectorAll('.risk_option');
    let hasValue = false;

    dropdowns.forEach(dropdown => {

      if (dropdown.value.trim() == 'Yes' || dropdown.value.trim().toLowerCase().includes('yes')) {
        hasValue = true;
        // highrisk=true;
      }
    });

    if (hasValue) {
      //alert('Yes, at least one dropdown has a value.');
      setRisk('high', '55');
    } else {
      setRisk('low', '-55');
      //alert('No, at least one dropdown has a value.');
    }
  }

  function checkHighriskValues() {
    var highrisk = false;
    const dropdowns = document.querySelectorAll('.higher_risk_option');
    const riskdropdowns = document.querySelectorAll('.risk_option');
    riskdropdowns.forEach(dropdown1 => {
      if (dropdown1.value.trim() == 'Yes' || dropdown1.value.trim().toLowerCase().includes('yes')) {

        highrisk = true;
      }
    });
    let hasValue2 = false;
    var otion2yestotal = 0;
    var otion2nototal = 0;
    dropdowns.forEach(dropdown => {
      if (dropdown.value.trim() == 'Yes' || dropdown.value.trim().toLowerCase().includes('yes')) {
        hasValue2 = true;
        otion2yestotal++;
      }
      if (dropdown.value.trim() == 'No' || dropdown.value.trim().toLowerCase().includes('no')) {
        otion2nototal++;
      }
    });
    //alert(otion2nototal);
    if (highrisk) {
      setRisk('high', '55');
    } else {
      if (otion2nototal == 3) {
        setRisk('low', '-75');
      }
      if (otion2nototal == 2 && otion2yestotal == 1) {
        setRisk('medium', '-20');
      }
      if (otion2nototal == 1 && otion2yestotal == 2) {
        setRisk('medium', '0');
      }

      if (otion2yestotal == 3) {
        setRisk('medium', '23');
      }
    }


    //      if(otion2yestotal==2){
    //          setRisk('medium','5');
    //      } 
    //      if(otion2yestotal==1){
    //          setRisk('medium','11');
    //      }
  }
  document.addEventListener('DOMContentLoaded', () => {
    checkriskValues();
    checkHighriskValues();
  });

  // Check on dropdown change
  document.querySelectorAll('.risk_option').forEach(dropdown => {
    dropdown.addEventListener('change', () => {
      checkriskValues();
    });
  });
  document.querySelectorAll('.higher_risk_option').forEach(dropdown => {
    dropdown.addEventListener('change', () => {
      checkHighriskValues();
    });
  });
  const decreaseButton = document.getElementById('decrease');
  const increaseButton = document.getElementById('increase');
  const hiddenValue = document.getElementById('level_risk_value');
  // Function to update the value
  function updateRiskValue(change) {
    let currentValue = parseInt(hiddenValue.value, 10); // Get the current value from the hidden field
    currentValue += change; // Adjust the value
    hiddenValue.value = currentValue; // Update the hidden field
    // displayValue.textContent = currentValue; // Update the display value

    //const needle = document.getElementById('iconRotate');

    //const riskLevel = document.getElementById('risk-level');
    // console.log(level); console.log(rotateval);
    var level = '';
    if (currentValue < -26) {
      level = 'low';
    }
    if (currentValue > -26 && currentValue < 26) {
      level = 'medium';
    }
    if (currentValue > 26) {
      level = 'high';
    }
    setRisk(level, currentValue)
    // jQuery('#level_risk').val(level);
    //jQuery('#level_risk_value').val(currentValue);   
  }

  // Attach event listeners for the buttons
  decreaseButton.addEventListener('click', () => updateRiskValue(-5));
  // Decrease by 1
  increaseButton.addEventListener('click', () => updateRiskValue(5));
  // Increase by 1
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
  jQuery('.remove-icon').on('click', function(e) {
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

  // Handle form submission
  /* document.getElementById('emailForm').addEventListener('submit', (e) => {
     e.preventDefault(); // Prevent actual submission for demonstration
     alert(`Submitted Emails: ${emailField.value}`);
   });*/
  jQuery(document).ready(function($) {
    var previousemail = '<?php echo $email; ?>';
    emails.push(previousemail);



  });
</script>
<style>
  meta[name="viewport"] {
    display: none;
    /* Ignore viewport settings */
  }

  .listItemWithNumber strong {
    height: 30px;
    width: 30px;
    margin-right: 10px;
    background: #5f9289;
    color: #fff;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 30px;
    margin-top: 5px;
  }

  .listItemWithIcon .iconBoxSm {
    min-width: 10px;
    width: 10px;
    height: 10px;
    background: #5f9289;
    margin-top: 7px;
    margin-right: 10px;
  }

  select,
  .wp-core-ui select {
    width: 100%;
    border: none;
    border-bottom: 1px solid #000;
    color: #000 !important;
    /* font-size: 20px !important; */
    height: 36px;
    margin-bottom: 12px;
    max-width: 100%;
    border-radius: 0;
  }

  .pdfHtml label {
    /*     font-size: 20px; */
    font-weight: 600;
    color: #1a1919;
  }

  @page {
    margin-left: 0;
    margin-right: 0;
  }

  @media print {
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
    }

    @page {
      margin-left: 0;
      margin-right: 0;
    }
  }

  body {
    margin: 0;
    padding: 0;
  }

  .noNeed {
    display: none !important;
  }

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
    margin-bottom: 10px;
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

  .pdf-capture {
    page-break-inside: avoid;
    /* Prevent breaks inside sections */
    margin: 0;
    /* Reset margins for consistency */
    padding: 10px;
    /* Add padding for cleaner layout */
  }

  #file_preview {
    background: #5f9289;
    padding: 15px;
  }

  #file_preview a.remove_file {
    color: black;
  }

  #file_preview img {
    border: 5px solid #fff;
    height: auto !important;
  }

  @media only screen and (max-width: 767px) {
    .pdfHtml label {
      /* font-size: 15px; */
    }
  }

  .skinCareSummary {}

  .skinCareSummary .minGr {
    padding: 5px;
    height: 100%;
  }

  .skinCareSummary .innerGrid {
    margin-top: -10px;
    margin-right: -10px;
    background-color: #fff;
    padding: 15px;
    border: 1px solid #5f9289;
    height: calc(100% + 10px);
  }

  .skinCareSummary .imgLogo {
    padding: 2px;
  }

  .skinCareSummary .imgLogo img {
    height: 100%;
    object-fit: cover;
    object-position: center center;

  }

  .skinCareSummary .innerGrid,
  .skinCareSummary .innerGrid p,
  .skinCareSummary .innerGrid * {
    margin-bottom: 0;
    font-size: clamp(18px, 4vw, 25px);
  }

  .bg_primary {
    background: #5f9289 !important;
  }

  .p_2 {
    padding: 0.7rem;
  }

  .p_3 {
    padding: 1rem;
  }

  .imgFulCover {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center center;
  }

  .generating .section41 .col-md-4 {
    width: 33.333333% !important;
  }

  .generating table .select_arrow {
    background-position-y: bottom !important;
    background-position-x: right !important;
    background-size: 70px !important;
    margin-top: 15px;
    /* text-align: center; */
  }
  
  /* Marker Photo Modal */
  #markerPhotoModal {
    position: fixed;
    inset: 0;
    background: rgba(90, 74, 90, 0.65);
    z-index: 100000;
    display: none;
    align-items: center;
    justify-content: center;
    font-family: 'Quicksand', sans-serif;
  }
  #markerPhotoModal.open { display: flex; }
  #markerPhotoModal .mp-dialog {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
    width: 92%;
    max-width: 460px;
    overflow: hidden;
  }
  #markerPhotoModal .mp-header {
    background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%);
    color: white;
    padding: 18px 22px;
    font-weight: 700;
    font-size: 17px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  #markerPhotoModal .mp-close {
    background: rgba(255,255,255,0.25);
    border: none;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    font-weight: 700;
    line-height: 1;
  }
  #markerPhotoModal .mp-body {
    padding: 24px 22px;
    color: #5A4A5A;
  }
  #markerPhotoModal .mp-spot-info {
    background: #FFF9F5;
    border: 1px dashed #FFE4EC;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 18px;
    color: #8A7A8A;
  }
  #markerPhotoModal .mp-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 8px;
  }
  #markerPhotoModal .mp-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 22px 12px;
    border: 2px solid #98D9C2;
    background: #D4F1E8;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #5A4A5A;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    font-size: 14px;
    text-align: center;
  }
  #markerPhotoModal .mp-btn:hover {
    background: #98D9C2;
    transform: translateY(-2px);
    color: #fff;
  }
  #markerPhotoModal .mp-btn svg { width: 32px; height: 32px; }
  #markerPhotoModal .mp-progress {
    margin-top: 14px;
    display: none;
    font-size: 13px;
    color: #5A4A5A;
  }
  #markerPhotoModal.uploading .mp-progress { display: block; }
  #markerPhotoModal.uploading .mp-options { opacity: 0.4; pointer-events: none; }
  #markerPhotoModal .mp-result {
    margin-top: 14px;
    display: none;
    background: #D4EDDA;
    border: 1px solid #98D9C2;
    border-radius: 10px;
    padding: 12px;
    text-align: center;
  }
  #markerPhotoModal .mp-result img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    margin-top: 8px;
  }
  #markerPhotoModal .mp-error {
    margin-top: 12px;
    display: none;
    background: #F8D7DA;
    border: 1px solid #E8829A;
    border-radius: 8px;
    padding: 10px 12px;
    color: #842029;
    font-size: 13px;
  }
  #markerPhotoModal .mp-footer {
    padding: 12px 22px 18px;
    text-align: right;
    border-top: 1px solid #f0e5e8;
  }
  #markerPhotoModal .mp-skip-btn {
    background: transparent;
    border: none;
    color: #8A7A8A;
    font-family: 'Quicksand', sans-serif;
    font-size: 13px;
    cursor: pointer;
    text-decoration: underline;
  }
</style>

<!-- Marker Photo Modal (v9.6.9) -->
<div id="markerPhotoModal" role="dialog" aria-modal="true" aria-labelledby="mpTitle">
  <div class="mp-dialog">
    <div class="mp-header">
      <span id="mpTitle">Attach Photo to This Spot</span>
      <button type="button" class="mp-close" aria-label="Close">&times;</button>
    </div>
    <div class="mp-body">
      <div class="mp-spot-info">
        <strong>Spot:</strong> <span id="mpCanvasLabel">-</span> &nbsp;|&nbsp;
        <strong>Position:</strong> <span id="mpCoords">-</span>
      </div>
      <div class="mp-options">
        <label class="mp-btn" for="mpFileInput">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
          Upload from Device
          <input type="file" id="mpFileInput" accept="image/*" style="display:none;">
        </label>
        <label class="mp-btn" for="mpCameraInput">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
            <circle cx="12" cy="13" r="4"></circle>
          </svg>
          Take Photo
          <input type="file" id="mpCameraInput" accept="image/*" capture="environment" style="display:none;">
        </label>
      </div>
      <div class="mp-progress" id="mpProgress">Uploading &amp; saving to patient history…</div>
      <div class="mp-result" id="mpResult">
        <strong>Photo attached!</strong>
        <div id="mpResultInner"></div>
      </div>
      <div class="mp-error" id="mpError"></div>
    </div>
    <div class="mp-footer">
      <button type="button" class="mp-skip-btn" id="mpSkipBtn">Skip — leave this spot without a photo</button>
    </div>
  </div>
</div>

<script>
/**
 * Marker Photo Modal (v9.6.9)
 * Opens when a user creates a red spot on a body chart canvas.
 * Photos are uploaded directly to the protected /patient-photos/ folder
 * via the amelia_upload_marker_photo AJAX endpoint (bypasses Media Library).
 */
(function() {
  var $ = jQuery;
  var currentCtx = null;
  
  var canvasLabels = {
    'frontCanvas': 'Front of Body',
    'backCanvas': 'Back of Body',
    'face1Canvas': 'Face (Side 1)',
    'face2Canvas': 'Face (Side 2)'
  };
  
  window.openMarkerPhotoModal = function(canvasId, x, y, markIndex) {
    currentCtx = { canvasId: canvasId, x: x, y: y, markIndex: markIndex };
    
    $('#mpCanvasLabel').text(canvasLabels[canvasId] || canvasId);
    $('#mpCoords').text('(' + Math.round(x) + ', ' + Math.round(y) + ')');
    
    $('#markerPhotoModal').removeClass('uploading').addClass('open');
    $('#mpProgress, #mpResult, #mpError').hide();
    $('#mpFileInput, #mpCameraInput').val('');
  };
  
  function closeModal() {
    $('#markerPhotoModal').removeClass('open uploading');
    currentCtx = null;
  }
  
  $(document).on('click', '#markerPhotoModal .mp-close, #markerPhotoModal #mpSkipBtn', closeModal);
  
  // Close on backdrop click
  $(document).on('click', '#markerPhotoModal', function(e) {
    if (e.target === this) closeModal();
  });
  
  // Handle file selection (for either upload or camera)
  $(document).on('change', '#mpFileInput, #mpCameraInput', function() {
    if (!this.files || !this.files[0] || !currentCtx) return;
    uploadMarkerPhoto(this.files[0]);
  });
  
  function uploadMarkerPhoto(file) {
    var appointmentId = $('#appoinment_id').val();
    if (!appointmentId) {
      showError('No appointment ID found. Please save the body chart first.');
      return;
    }
    if (!currentCtx) return;
    
    var formData = new FormData();
    formData.append('action', 'amelia_upload_marker_photo');
    formData.append('nonce', (typeof ajax !== 'undefined' && ajax.nonce) ? ajax.nonce : '');
    formData.append('appointment_id', appointmentId);
    formData.append('body_location', currentCtx.canvasId);
    formData.append('marker_x', currentCtx.x);
    formData.append('marker_y', currentCtx.y);
    formData.append('marker_photo', file);
    
    $('#markerPhotoModal').addClass('uploading');
    $('#mpError').hide();
    
    $.ajax({
      url: (typeof ajax !== 'undefined' && ajax.url) ? ajax.url : ajaxurl,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(resp) {
        $('#markerPhotoModal').removeClass('uploading');
        
        if (resp && resp.success && resp.data && resp.data.file_url) {
          // Attach photo info to the mark entry
          if (window.bodyChartMarks && window.bodyChartMarks[currentCtx.canvasId]) {
            var mark = window.bodyChartMarks[currentCtx.canvasId][currentCtx.markIndex];
            if (mark) {
              mark.photoId = resp.data.photo_id;
              mark.photoUrl = resp.data.file_url;
              mark.lesionId = resp.data.lesion_id;
            }
          }
          
          // Redraw to show the camera badge
          if (typeof window.redrawBodyChartMarks === 'function') {
            window.redrawBodyChartMarks(currentCtx.canvasId);
          }
          
          // Show success preview
          $('#mpResultInner').html('<img src="' + resp.data.file_url + '" alt="Uploaded photo">');
          $('#mpResult').show();
          
          // Auto-close after 1.5s
          setTimeout(closeModal, 1500);
        } else {
          var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Upload failed';
          showError(msg);
        }
      },
      error: function(xhr) {
        $('#markerPhotoModal').removeClass('uploading');
        showError('Network error during upload (HTTP ' + xhr.status + ')');
      }
    });
  }
  
  function showError(msg) {
    $('#mpError').text(msg).show();
    $('#markerPhotoModal').removeClass('uploading');
  }
  
  // Escape key to close
  $(document).on('keydown', function(e) {
    if (e.key === 'Escape' && $('#markerPhotoModal').hasClass('open')) {
      closeModal();
    }
  });
  
  /* ------------------------------------------------------------
   * v9.7.0 — Hover Thumbnail Preview
   * When the user hovers near a red spot with a photo, show a
   * floating thumbnail of the attached photo next to the cursor.
   * ------------------------------------------------------------ */
  var $hoverTip = null;
  var lastHoverMark = null;
  var HOVER_RADIUS = 12; // px proximity for hover hit-detection
  
  function ensureHoverTip() {
    if (!$hoverTip) {
      $hoverTip = $(
        '<div id="markerHoverPreview" style="' +
        'position:absolute;z-index:99998;display:none;' +
        'background:#fff;border:2px solid #98D9C2;border-radius:10px;' +
        'padding:6px;box-shadow:0 6px 20px rgba(0,0,0,0.25);' +
        'pointer-events:none;font-family:Quicksand,sans-serif;font-size:11px;color:#5A4A5A;' +
        'max-width:220px;">' +
        '<img id="markerHoverImg" style="display:block;max-width:200px;max-height:200px;border-radius:6px;" />' +
        '<div id="markerHoverMeta" style="margin-top:4px;text-align:center;color:#8A7A8A;"></div>' +
        '</div>'
      ).appendTo('body');
    }
    return $hoverTip;
  }
  
  function findNearbyMark(canvasId, x, y) {
    var list = window.bodyChartMarks && window.bodyChartMarks[canvasId];
    if (!list) return null;
    for (var i = 0; i < list.length; i++) {
      var m = list[i];
      if (!m.photoUrl) continue;
      if (Math.abs(m.x - x) <= HOVER_RADIUS && Math.abs(m.y - y) <= HOVER_RADIUS) {
        return m;
      }
    }
    return null;
  }
  
  function showHoverTip(evt, mark) {
    var $tip = ensureHoverTip();
    if (lastHoverMark !== mark) {
      $('#markerHoverImg').attr('src', mark.photoUrl);
      var meta = 'Click to attach another • Drag to view';
      if (mark.lesionId) {
        meta = 'Lesion #' + String(mark.lesionId).substring(0, 8);
      }
      $('#markerHoverMeta').text(meta);
      lastHoverMark = mark;
    }
    // Position next to cursor but keep in viewport
    var pad = 14;
    var tipW = $tip.outerWidth();
    var tipH = $tip.outerHeight();
    var winW = $(window).width();
    var winH = $(window).height();
    var left = evt.pageX + pad;
    var top = evt.pageY + pad;
    if (left + tipW > $(window).scrollLeft() + winW) {
      left = evt.pageX - tipW - pad;
    }
    if (top + tipH > $(window).scrollTop() + winH) {
      top = evt.pageY - tipH - pad;
    }
    $tip.css({ left: left + 'px', top: top + 'px' }).show();
  }
  
  function hideHoverTip() {
    if ($hoverTip) $hoverTip.hide();
    lastHoverMark = null;
  }
  
  // Attach hover listeners to every body-chart canvas
  var canvasIds = ['frontCanvas', 'backCanvas', 'face1Canvas', 'face2Canvas'];
  canvasIds.forEach(function(canvasId) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    canvas.addEventListener('mousemove', function(evt) {
      var rect = canvas.getBoundingClientRect();
      var x = evt.clientX - rect.left;
      var y = evt.clientY - rect.top;
      var mark = findNearbyMark(canvasId, x, y);
      if (mark) {
        canvas.style.cursor = 'zoom-in';
        showHoverTip(evt, mark);
      } else {
        canvas.style.cursor = 'crosshair';
        hideHoverTip();
      }
    });
    
    canvas.addEventListener('mouseleave', hideHoverTip);
  });
  
  // Hide tooltip if any modal opens
  $(document).on('click', function() {
    if ($('#markerPhotoModal').hasClass('open')) hideHoverTip();
  });
})();
</script>
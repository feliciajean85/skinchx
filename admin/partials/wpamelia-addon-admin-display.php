<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script> 
<?php 
$services=get_all_services(); 
$combined_services = array();
$combined_service_names = '';
$auto_generate_pdf = isset($_GET['auto_generate_pdf']) && $_GET['auto_generate_pdf'] == '1';
$saved_report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;

if (isset($_GET['combined_services'])) {
    $raw_services = sanitize_text_field(wp_unslash($_GET['combined_services']));
    $combined_services = array_filter(array_map('intval', explode(',', $raw_services)));
    if (!empty($combined_services) && !empty($services)) {
        $service_lookup = array();
        foreach ($services as $serv) {
            $service_lookup[intval($serv->id)] = $serv->name;
        }
        $selected_names = array();
        foreach ($combined_services as $sid) {
            if (isset($service_lookup[$sid])) {
                $selected_names[] = $service_lookup[$sid];
            }
        }
        if (!empty($selected_names)) {
            $combined_service_names = implode(', ', $selected_names);
        }
    }
}

// If auto-generate PDF and saved report ID, load saved report data
$saved_report_data = null;
if ($auto_generate_pdf && $saved_report_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_combined_reports';
    $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $saved_report_id), ARRAY_A);
    if ($report) {
        $saved_report_data = $report;
        if (!empty($report['form_data'])) {
            $saved_report_data['form_data'] = json_decode($report['form_data'], true);
        }
        if (!empty($report['customer_ids'])) {
            $saved_report_data['customer_ids'] = json_decode($report['customer_ids'], true);
        }
    }
}
?>
<style>
  /* Set the dimensions of the chart */
  .chartContainer {
    width: 350px; /* Set your desired width */
    height: 250px; /* Set your desired height */
    margin: auto; /* Center the chart */
  }
  canvas {
    display: block;
    width: 100% !important; /* Ensure the canvas fits the container */
    height: 100% !important; /* Ensure the canvas fits the container */
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
  <?php if ($auto_generate_pdf): ?>
  /* Hide entire page when auto-generating PDF - only PDF should open */
  body {
    display: none !important;
    visibility: hidden !important;
  }
  <?php endif; ?>
  
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
  
  .generating .chartContainer {
    width: 350px !important;
    height: 250px !important;
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
    
    main, .pdf-capture:not(.generating .pdf-capture), .container {
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
  }
</style>

<?php 
$referer_url = (wp_get_referer())?wp_get_referer():admin_url('admin.php?page=amelia-report');
?>
<form method="post" id="service_chart_form">
  <div class="d-flexs" id="serviceFilterWrap">

      <div>
    <label for="serviceFilter">Service:</label>
    <select id="serviceFilter" class="select2drop" name="service_id">
        <option value="">Select Service</option>
        <?php if(!empty($services)){ foreach($services as $serv){ ?>
        <option value="<?php echo $serv->id;?>"><?php echo $serv->name;?></option>
        
        <?php }  }?>
    </select>


</div>
    </div>
         <main class="pdfHtml" id="content-to-pdf">
    
      <section class="section1" style="margin-top:20px;">
         
      
        <div class="container">
         
           
        </div>
      </section>
      <section class="section1 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/1.jpg" alt="" />
        </div>
      </section>
      <section class="section2 pdf-capture">
        <div class="container">
            <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/header.jpg" alt="" />
            <div class="row" style="display:flex;flex-wrap:wrap;">
                <div class="col-md-5" style="width:41.66666667%;">
                    <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/left-2.jpg" alt="" />
                </div>
                <div class="secondSectonTextCol col-md-7 py-5" style="width:58.33333333%;">
                    <div class="mb-4">
                   <div class="detailedAnalysisImg">
                
                    <div id="file_preview">
                      <?php if(isset($body_chart_data['uploaded_file_url']) && $body_chart_data['uploaded_file_url']) echo '  <img class="" src="'.$body_chart_data['uploaded_file_url'].'"
                      alt=""/>'; else echo '  <img class="" src="https://placehold.co/300x300"
                      alt=""/>';?>
                    
                    </div>
                       <input type="file" id="image_file" style="display: none;" accept=".jpg,.jpeg,.png,.JPG,.JPEG,.PNG" />
                       <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;">
                           <button type="button" id="select_from_media_library" class="button button-primary removefrompdf">Select from Media Library</button>
                       </div>
                     
                       <input type="hidden" id="photo_file_url" name="uploaded_file_url" value="">
                  </div>
      
           
                    </div>
                    <div class="mb-1">
                            <div class="formInput">
                          <label>Email</label>
                         <input type="email" name="email" value="" id="service_report_to_email">
                          <input type="hidden" name="report_email" value="" id="report_to_email">
                          </div>
                    </div>
                 
                
                    <div class="formInput mb-1">
                         <label>Company Contact Name:</label>
                         <input name="contact_name" type="text"/>
                          </div>
                    <div class="formInput mb-1">
                         <label>Company Contact Phone:</label>
                         <input name="phone" type="text"/>
                          </div>
                     
                         <div class="formInput mb-1">
                         <label>Company Address:</label>
                         <textarea name="details"></textarea>
                          </div>
                     <p class="mb-2">
                        <strong>Service:</strong> 
                        <span id="servicename"></span>
                    </p>
                    <p class="mb-2">
                        <strong>Number of Total Participants:</strong> 
                        <span id="totalparticipent"></span>
                    </p>
                      <p class="mb-2">
                        <strong>Number of Total Attended:</strong> 
                        <span id="totalattended"></span>
                    </p>
                    <p class="mb-2">
                        <strong>Number of Total Immediate Referrals:</strong> 
                        <span id="immediateReferrals"></span>
                    </p>
                    <p class="mb-2">
                        <strong>Number of Total Month Referrals:</strong> 
                        <span id="monthReferrals"></span>
                    </p>
                    <h3 class="text-uppercase pt-4 sectionTitle">
                        Thank you for choosing skin chx to facilitate your workplace skin check program.
                    </h3>
                </div>
            </div>
            <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>
      <section class="section3 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/3.jpg" alt="" />
        </div>
      </section>
      <section class="section4 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/header.jpg" alt="" />
          <div class="innerContainer">
              <div class="row" style="display:flex;flex-wrap:wrap;">
                <div class="col-lg-6 mb-4" style="width:50%">
                    <h2 class="sectionTitle text-center chartTitle">Age Group Captured</h2>
                    <div class="chartContainer">
                        <canvas id="ageGroupChart" style="width:100px;"></canvas>
                    </div>
                 
                </div>
                <div class="col-lg-6 mb-4" style="width:50%">
                    <h2 class="sectionTitle text-center chartTitle">PERCENTAGE OF PEOPLE WITH HISTORY OF SKIN CANCER</h2>
                    <div class="chartContainer">
                        <canvas id="skincancerPercent" style="width:100px;"></canvas>
                    </div>
                </div>
                <div class="col-lg-6 mb-4" style="width:50%">
                    <h2 class="sectionTitle text-center chartTitle">Outdoor Worker</h2>
                    <div class="chartContainer">
                        <canvas id="indoorChart" style="width:100px;"></canvas>
                    </div>
                </div>
                <div class="col-lg-6 mb-4" style="width:50%">
                    <h2 class="sectionTitle text-center chartTitle">Lesions Of Concern-Outdoor Worker</h2>
                    <div class="chartContainer">
                        <canvas id="bodyChart" style="width:100px;"></canvas>
                    </div>
                </div>
              </div>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>
      <section class="section4 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/header.jpg" alt="" />
          <div class="innerContainer">
              <div class="row" style="display:flex;flex-wrap:wrap;">
                <div class="col-lg-6 mb-4" style="width:50%">
                    <h2 class="sectionTitle text-center chartTitle">Hours Spent outside at work</h2>
                    <div class="chartContainer">
                        <canvas id="spentChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6 mb-4" style="width:50%">
                    <h2 class="sectionTitle text-center chartTitle">Hours Spent outside at home</h2>
                    <div class="chartContainer">
                        <canvas id="spentChart2"></canvas>
                    </div>
                </div>
                <div class="col-lg-6 mb-4" style="width:50%">
                    <h2 class="sectionTitle text-center chartTitle">SUNSCREEN AT HOME</h2>
                    <div class="chartContainer">
                        <canvas id="sunscreenhomeChart" style="width:100px;"></canvas>
                    </div>
                </div>
                <div class="col-lg-6 mb-4" style="width:50%">
                    <h2 class="sectionTitle text-center chartTitle">IS WORKPLACE PPE SUFFICIENT?</h2>
                    <div class="chartContainer">
                        <canvas id="ppe" style="width:100px;"></canvas>
                    </div>
                </div>
              </div>
          </div>
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>
  
      <section class="sectionSumarry pdf-capture">
        <div class="container">
            <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/header.jpg" alt="" />
            <div class="formInput mb-1">
                <label>Summary:</label>
                <textarea name="chat_gpt_sumarry" id="chat_gpt_sumarry"></textarea>
            </div>
            <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/footer.jpg" alt="" />
        </div>
      </section>

      <section class="section9 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/9.jpg" alt="" />
        </div>
      </section>
      <section class="section10 pdf-capture">
        <div class="container">
          <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL?>/admin/images/10.jpg" alt="" />
        </div>
      </section>
              <div class="amlia_form_btns sticky-bottom bg-white py-2">
       <button class="btn btn-primary" name="submit" type="submit" id="save_service_chart">Save</button>
       <button class="btn btn-primary" type="button" id="pdf_generator" id="generate_pdf">Pdf Generate</button>
          
     
        
    <button class="btn btn-primary" id="openModalBtn" type="button" >Send Report</button>
    </div>
  
       <div id="alert-message" style="display: none; padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; margin-top: 10px;text-align: center;">
        <span id="notification-message"></span>
        </div>
    </main>
   
     </form> 
   <div id="emailModal" class="modal">
    <div class="modal-content">
      <button type="button" class="btn-close modal-close-icon" id="closeModalBtn" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      <h2>Add Email Addresses</h2>
      <input class="w-100" type="email" id="emailInput" placeholder="Enter email" />
      <button class="w-100 mx-0 btn-success btn" id="addEmailBtn">Add Email</button>
      
      <ul class="email-list w-100" id="emailList">
         
        </ul>
<button class="btn btn-primary" type="button" id="save_send_service_email">Send Report</button>
    </div>
  </div>
<script>
var ajax_url='<?php echo admin_url('admin-ajax.php');?>';
var combinedServices = <?php echo wp_json_encode($combined_services); ?>;
var isCombinedMode = Array.isArray(combinedServices) && combinedServices.length > 0;
var combinedServiceNames = <?php echo wp_json_encode($combined_service_names ? $combined_service_names : 'Combined Services'); ?>;
// When preview is embedded from Settings > Combined Group Report, we can also
// receive a filtered customer list so that charts reflect only selected people.
var combinedCustomers = [];
var combinedCustomerNames = '';

   


</script>
<script>
       let emails = [];
    jQuery(document).ready(function($) {
    $('.select2drop').select2();
    let qaServiceChart;let chartInstances = {};
    //defineageChart();
   
   
    // Fetch Chart Data via AJAX
    $('#serviceFilter').on('change', function() {
        jQuery('#customerFilter').val('0').select2(); 
      filterChart('customerchange');
    });
    $('#customerFilter').on('change', function() {
      filterChart('');
    });
    if (isCombinedMode) {
        $('#serviceFilter').prop('disabled', true);
        $('#servicename').text(combinedServiceNames || 'Combined Services');
        loadCombinedReport(combinedServices, combinedServiceNames || 'Combined Services');
        $('#goBackBtn').hide();
        $('#serviceFilterWrap').hide();
    }
        function resetChart(chartId) {
                // Check if the chart instance exists
                if (chartInstances[chartId]) {
                    chartInstances[chartId].destroy(); // Destroy the existing chart instance
                    delete chartInstances[chartId]; // Remove it from the global object
                }
            }
            const randomColors=['#5eBeB7', '#c0a173', '#5f9289', '#E2A473','#77BAB7'];
            function getRandomColors(count) {
                return randomColors.slice(0, count);
                //count=4;
             /* return Array.from({ length: count }, () => {
                const randomIndex = Math.floor(Math.random() * randomColors.length);
                return randomColors[randomIndex];
              });*/
            }
          function spentChart2(jsonData){
        const labels = Object.keys(jsonData);
            const data = Object.values(jsonData);
//const total = Object.values(data).reduce((sum, value) => sum + Number(value), 0);


        // Calculate percentages for each value
        const total = data.reduce((sum, value) => sum + Number(value), 0);
        //const percentages = data.map(value => ((value / total) * 100).toFixed(0)); // Rounded percentages
        // const values = data.map(value => Number(value) === 0 ? 0.001 : Number(value));
        const percentages = data.map(value => {
            return total > 0 ? (Number(value) / total * 100).toFixed(2) : 0;
        });
           const modifiedLabels = labels.map((label, index) => {
            if(!isNaN(percentages[index]))
             return label + ' (' + percentages[index] + '%)';
              else
                return label +' (0%)';
                
         });
        // Generate dynamic random colors for each label
//        const randomColors = labels.map(() => {
//            return `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`;
//        });
         const randomColors=['#5eBeB7', '#c0a173', '#f4f4f4', '#FFFFFF', '#000000'];

        // Assign colors (Red for 0%, Random Color for non-zero values)
        const backgroundColors = data.map(value => value === "0" ? '#FF0000' : getRandomColor());
        // Create the chart
        const ctx = document.getElementById('spentChart2').getContext('2d');
        chartInstance= new Chart(ctx, {
            type: 'pie', // Chart type
            data: {
                labels: modifiedLabels,
                  datasets: [{
                    label: '',
                    data: data, // Counts for each label
                    backgroundColor: getRandomColors(labels.length), // Dynamic colors
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: '' // Chart title
                    },
                    legend: {
                        display: true,
                        position: 'right' // Legend position
                    },
                  datalabels: {
                     display: false // Don't show labels inside the chart anymore
                      }
                },
                responsive: true,
                maintainAspectRatio: false // Disable maintaining aspect ratio to apply custom dimensions
            },
            plugins: [ChartDataLabels] // Register the Data Labels plugin
        });
            chartInstances['spentChart2'] = chartInstance;

        }
        function spentChart(jsonData){
              const labels = Object.keys(jsonData);
            const data = Object.values(jsonData);
//const total = Object.values(data).reduce((sum, value) => sum + Number(value), 0);


        // Calculate percentages for each value
        const total = data.reduce((sum, value) => sum + Number(value), 0);
        //const percentages = data.map(value => ((value / total) * 100).toFixed(0)); // Rounded percentages
        // const values = data.map(value => Number(value) === 0 ? 0.001 : Number(value));
        const percentages = data.map(value => {
            return total > 0 ? (Number(value) / total * 100).toFixed(2) : 0;
        });
               const modifiedLabels = labels.map((label, index) => {
            if(!isNaN(percentages[index]))
             return label + ' (' + percentages[index] + '%)';
              else
                return label +' (0%)';
                
         });
        // Generate dynamic random colors for each label
//        const randomColors = labels.map(() => {
//            return `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`;
//        });

        const randomColors=['#5eBeB7', '#c0a173', '#f4f4f4', '#FFFFFF', '#000000'];
        // Assign colors (Red for 0%, Random Color for non-zero values)
        const backgroundColors = data.map(value => value === "0" ? '#FF0000' : getRandomColor());
        // Create the chart
        const ctx = document.getElementById('spentChart').getContext('2d');
        chartInstance= new Chart(ctx, {
            type: 'pie', // Chart type
            data: {
                labels: modifiedLabels,
                  datasets: [{
                    label: '',
                    data: data, // Counts for each label
                    backgroundColor: getRandomColors(labels.length), // Dynamic colors
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: '' // Chart title
                    },
                    legend: {
                        display: true,
                        position: 'right' // Legend position
                    },
                      datalabels: {
                     display: false // Don't show labels inside the chart anymore
                      }
//                    datalabels: {
//                        color: '#fff', // Label color
//                        font: {
//                              family: 'Montserrat',
//                            weight: 'bold',
//                            size: 16
//                        },
//                        formatter: (value, ctx) => {
//                            // Return the percentage
//                            const index = ctx.dataIndex;
//                            return percentages[index] > 0 ? percentages[index]+ '%' : '';
//                        }
//                    }
                },
                responsive: true,
                maintainAspectRatio: false // Disable maintaining aspect ratio to apply custom dimensions
            },
            plugins: [ChartDataLabels] // Register the Data Labels plugin
        });
            chartInstances['spentChart'] = chartInstance;

        }  
        function body_report(jsonData){
       const labels = Object.keys(jsonData);
            const data = Object.values(jsonData);
//const total = Object.values(data).reduce((sum, value) => sum + Number(value), 0);


        // Calculate percentages for each value
        const total = data.reduce((sum, value) => sum + Number(value), 0);
        //const percentages = data.map(value => ((value / total) * 100).toFixed(0)); // Rounded percentages
        // const values = data.map(value => Number(value) === 0 ? 0.001 : Number(value));
        const percentages = data.map(value => {
            return total > 0 ? (Number(value) / total * 100).toFixed(2) : 0;
        });
        const modifiedLabels = labels.map((label, index) => {
            if(!isNaN(percentages[index]))
             return label + ' (' + percentages[index] + '%)';
              else
                return label +' (0%)';
                
         });
        // Generate dynamic random colors for each label
    /*    const randomColors = labels.map(() => {
            return `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`;
        });*/
        const randomColors=['#5eBeB7', '#c0a173', '#f4f4f4', '#FFFFFF', '#000000'];

        // Assign colors (Red for 0%, Random Color for non-zero values)
        const backgroundColors = data.map(value => value === "0" ? '#FF0000' : getRandomColor());
        // Create the chart
        const ctx = document.getElementById('bodyChart').getContext('2d');
       chartInstance= new Chart(ctx, {
            type: 'pie', // Chart type
            data: {
                labels: modifiedLabels,
                  datasets: [{
                    label: '',
                    data: data, // Counts for each label
                    backgroundColor: getRandomColors(labels.length), // Dynamic colors
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: '' // Chart title
                    },
                    legend: {
                        display: true,
                        position: 'right' // Legend position
                    },
                      datalabels: {
                     display: false // Don't show labels inside the chart anymore
                      }
//                    datalabels: {
//                        color: '#fff', // Label color
//                        font: {
//                              family: 'Montserrat',
//                            weight: 'bold',
//                            size: 16
//                        },
//                        formatter: (value, ctx) => {
//                            // Return the percentage
//                            const index = ctx.dataIndex;
//                          return percentages[index] > 0 ? percentages[index]+ '%' : '';
//                        }
//                    }
                },
                responsive: true,
                maintainAspectRatio: false // Disable maintaining aspect ratio to apply custom dimensions
            },
            plugins: [ChartDataLabels] // Register the Data Labels plugin
        });
            chartInstances['bodyChart'] = chartInstance;
        }
         function getRandomColor() {
            return `#${Math.floor(Math.random() * 16777215).toString(16)}`;
        }
     function renderAgeChart(jsonData){
         
          const labels = Object.keys(jsonData);
            const data = Object.values(jsonData);
//const total = Object.values(data).reduce((sum, value) => sum + Number(value), 0);


        // Calculate percentages for each value
        const total = data.reduce((sum, value) => sum + Number(value), 0);
        //const percentages = data.map(value => ((value / total) * 100).toFixed(0)); // Rounded percentages
        // const values = data.map(value => Number(value) === 0 ? 0.001 : Number(value));
        const percentages = data.map(value => {
            return total > 0 ? (Number(value) / total * 100).toFixed(2) : 0;
        });
         //console.log(percentages);
          const modifiedLabels = labels.map((label, index) => {
            if(!isNaN(percentages[index]))
             return label + ' (' + percentages[index] + '%)';
              else
                return label +' (0%)';
                
         });
        // Generate dynamic random colors for each label
      /*  const randomColors = labels.map(() => {
            return `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`;
        });
*/
        const randomColors=['#5eBeB7', '#c0a173', '#f4f4f4', '#FFFFFF', '#000000'];
        // Assign colors (Red for 0%, Random Color for non-zero values)
        const backgroundColors = data.map(value => value === "0" ? '#FF0000' : getRandomColor());
        // Create the chart
        const ctx = document.getElementById('ageGroupChart').getContext('2d');
       chartInstance= new Chart(ctx, {
            type: 'pie', // Chart type
            data: {
                labels: modifiedLabels,
                  datasets: [{
                    label: '',
                    data: data, // Counts for each label
                    backgroundColor: getRandomColors(labels.length), // Dynamic colors
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: '' // Chart title
                    },
                    legend: {
                        display: true,
                        position: 'right' // Legend position
                    },
                   datalabels: {
                display: false // Don't show labels inside the chart anymore
               }
                },
                responsive: true,
                maintainAspectRatio: false // Disable maintaining aspect ratio to apply custom dimensions
            },
            plugins: [ChartDataLabels] // Register the Data Labels plugin
        });
            chartInstances['ageGroupChart'] = chartInstance;
     }      
        function skincancerPercent(jsonData){
            const labels = Object.keys(jsonData);
            const data = Object.values(jsonData);



        // Calculate percentages for each value
        const total = data.reduce((sum, value) => sum + value, 0);
        const percentages = data.map(value => ((value / total) * 100).toFixed(0)); // Rounded percentages
       const modifiedLabels = labels.map((label, index) => {
            if(!isNaN(percentages[index]))
             return label + ' (' + percentages[index] + '%)';
              else
                return label +' (0%)';
                
         });
        // Generate dynamic random colors for each label
     /*   const randomColors = labels.map(() => {
            return `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`;
        });*/
        const randomColors=['#5eBeB7', '#c0a173', '#f4f4f4', '#FFFFFF', '#000000'];
        // Create the chart
        const ctx = document.getElementById('skincancerPercent').getContext('2d');
       chartInstance= new Chart(ctx, {
            type: 'pie', // Chart type
            data: {
                labels: modifiedLabels , // Labels (e.g., Yes, No)
                datasets: [{
                    data: data, // Counts for each label
                    backgroundColor: getRandomColors(labels.length), // Dynamic colors
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: '' // Chart title
                    },
                    legend: {
                        display: true,
                        position: 'right' // Legend position
                    },
                    datalabels: {
                      display:false
                    }
                },
                responsive: true,
                maintainAspectRatio: false // Disable maintaining aspect ratio to apply custom dimensions
            },
            plugins: [ChartDataLabels] // Register the Data Labels plugin
        });
            chartInstances['skincancerPercent'] = chartInstance;
     }    
        function sunscreenhomeChart(jsonData){
            const labels = Object.keys(jsonData);
             const data = Object.values(jsonData);



        // Calculate percentages for each value
        const total = data.reduce((sum, value) => sum + value, 0);
        const percentages = data.map(value => ((value / total) * 100).toFixed(0)); // Rounded percentages
        const modifiedLabels = labels.map((label, index) => {
            if(!isNaN(percentages[index]))
             return label + ' (' + percentages[index] + '%)';
              else
                return label +' (0%)';
                
         });
        // Generate dynamic random colors for each label
        /*const randomColors = labels.map(() => {
            return `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`;
        });
*/
        const randomColors=['#5eBeB7', '#c0a173', '#f4f4f4', '#FFFFFF', '#000000'];
        // Create the chart
        const ctx = document.getElementById('sunscreenhomeChart').getContext('2d');
       chartInstance= new Chart(ctx, {
            type: 'pie', // Chart type
            data: {
                labels: modifiedLabels, // Labels (e.g., Yes, No)
                datasets: [{
                    data: data, // Counts for each label
                    backgroundColor: getRandomColors(labels.length), // Dynamic colors
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: '' // Chart title
                    },
                    legend: {
                        display: true,
                        position: 'right' // Legend position
                    },
                    datalabels: {
                      display:false
                    }
                },
                responsive: true,
                maintainAspectRatio: false // Disable maintaining aspect ratio to apply custom dimensions
            },
            plugins: [ChartDataLabels] // Register the Data Labels plugin
        });
            chartInstances['sunscreenhomeChart'] = chartInstance;
     }    
        function indoorChart(indoorChart){
            //const jsonData = {"Yes": 14, "No": 86};

        // Extract labels (keys) and values from the JSON
        const labels = Object.keys(indoorChart);
        const data = Object.values(indoorChart);



        // Calculate percentages for each value
        const total = data.reduce((sum, value) => sum + value, 0);
        const percentages = data.map(value => ((value / total) * 100).toFixed(0)); // Rounded percentages
       const modifiedLabels = labels.map((label, index) => {
            if(!isNaN(percentages[index]))
             return label + ' (' + percentages[index] + '%)';
              else
                return label +' (0%)';
                
         });
        // Generate dynamic random colors for each label
        /*const randomColors = labels.map(() => {
            return `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`;
        });*/
        const randomColors=['#5eBeB7', '#c0a173', '#f4f4f4', '#FFFFFF', '#000000'];
        // Create the chart
        const ctx = document.getElementById('indoorChart').getContext('2d');
       chartInstance= new Chart(ctx, {
            type: 'pie', // Chart type
            data: {
                labels: modifiedLabels, // Labels (e.g., Yes, No)
                datasets: [{
                    data: data, // Counts for each label
                    backgroundColor: getRandomColors(labels.length), // Dynamic colors
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: '' // Chart title
                    },
                    legend: {
                        display: true,
                        position: 'right' // Legend position
                    },
                     datalabels: {
                        display:false
                    }
//                    datalabels: {
//                        color: '#fff', // Label color
//                        font: {
//                            family: 'Montserrat',
//                            weight: 'bold',
//                            size: 16
//                        },
//                        formatter: (value, ctx) => {
//                            // Return the percentage
//                            const index = ctx.dataIndex;
//                            return percentages[index] + '%';
//                        }
//                    }
                },
                responsive: true,
                maintainAspectRatio: false // Disable maintaining aspect ratio to apply custom dimensions
            },
            plugins: [ChartDataLabels] // Register the Data Labels plugin
        });
            chartInstances['outdoorchart'] = chartInstance;
     }     
        function ppe(jsonData){
            //const jsonData = {"Yes": 14, "No": 86};

        // Extract labels (keys) and values from the JSON
        const labels = Object.keys(jsonData);
        const data = Object.values(jsonData);



        // Calculate percentages for each value
        const total = data.reduce((sum, value) => sum + value, 0);
        const ppe_percentages = data.map(value => ((value / total) * 100).toFixed(0)); // Rounded percentages
        console.log(ppe_percentages);console.log(total);
        // Generate dynamic random colors for each label
        /*const randomColors = labels.map(() => {
            return `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`;
        });*/
        const modifiedLabels = labels.map((label, index) => {
            if(!isNaN(ppe_percentages[index]))
             return label + ' (' + ppe_percentages[index] + '%)';
              else
                return label +' (0%)';
                
         });
        const randomColors=['#5eBeB7', '#c0a173', '#f4f4f4', '#FFFFFF', '#000000'];
        // Create the chart
        const ctx = document.getElementById('ppe').getContext('2d');
       chartInstance= new Chart(ctx, {
            type: 'pie', // Chart type
            data: {
                labels: modifiedLabels, // Labels (e.g., Yes, No)
                datasets: [{
                    data: data, // Counts for each label
                    backgroundColor: getRandomColors(labels.length), // Dynamic colors
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: '' // Chart title
                    },
                    legend: {
                        display: true,
                        position: 'right' // Legend position
                    },
                    datalabels: {
                      display:false
                    }
                },
                responsive: true,
                maintainAspectRatio: false // Disable maintaining aspect ratio to apply custom dimensions
            },
            plugins: [ChartDataLabels] // Register the Data Labels plugin
        });
            chartInstances['ppe'] = chartInstance;
     }  
         
   function renderReportData(response, options = {}) {
        console.log(response.agedata);
         resetChart('ageGroupChart');
         resetChart('outdoorchart');
        resetChart('spentChart');
        resetChart('spentChart2');
        resetChart('bodyChart');
        resetChart('skincancerPercent');
        resetChart('sunscreenhomeChart');
        resetChart('ppe');
        if (response.agedata) {
           renderAgeChart(response.agedata);
        }  
        if (response.cancerHistorydata) {
           skincancerPercent(response.cancerHistorydata);
        } 
        if (response.body_report) {
           body_report(response.body_report);
        }
        if (response.outdoordata) {
           indoorChart(response.outdoordata);
        }
        if (response.spent_time_outside_at_home) {
          spentChart2(response.spent_time_outside_at_home);
           }
          if (response.spent_time_outside_at_work) {
           spentChart(response.spent_time_outside_at_work);
          }
        if (response.sunscreen) {
           sunscreenhomeChart(response.sunscreen);
          }
         if (response.ppe) {
           ppe(response.ppe);
          }  
        if (response.totalparticipent !== undefined) {
           jQuery('#totalparticipent').text(response.totalparticipent);
          } 
         if (response.totalattended !== undefined) {
           jQuery('#totalattended').text(response.totalattended);
          } 
        if (response.chatGptSumarry) {
           jQuery('#chat_gpt_sumarry').val(response.chatGptSumarry);
          }
           if (response.referal) {
           jQuery('#monthReferrals').text(response.referal.month); jQuery('#immediateReferrals').text(response.referal.imidiate);
          } 
        if (response.service_save_data) {
            if(response.service_save_data.file){
                 jQuery('#file_preview').html('<img src="'+response.service_save_data.file+'" alt="Preview" class="w-100"   style="max-width: 300px; margin-top: 10px;">');
                jQuery('#photo_file_url').val(response.service_save_data.file);
            }
               jQuery('#service_report_to_email').val(response.service_save_data.email);
               jQuery('#report_to_email').val(response.service_save_data.email);
                  if(response.service_save_data.email){
                 jQuery('#emailList').html('<li>'+response.service_save_data.email+'<button data-email="'+response.service_save_data.email+'" class="remove-icon" title="Remove Email" fdprocessedid="n3c7h59">×</button></li>');
                   emails.push(response.service_save_data.email);  
                }
            
                 jQuery("input[name='contact_name']").val(response.service_save_data.contact_name);
                  jQuery("input[name='phone']").val(response.service_save_data.phone);
                 jQuery("textarea[name='details']").val(response.service_save_data.details);
          }
        if(response.chatGptSumarry && (!response.service_save_data || !response.service_save_data.chat_gpt_sumarry)){
            jQuery("#chat_gpt_sumarry").val(response.chatGptSumarry);
        }
        if(response.service_save_data && response.service_save_data.chat_gpt_sumarry){
            jQuery("#chat_gpt_sumarry").val(response.service_save_data.chat_gpt_sumarry);
        }
            const textarea2 = document.getElementById('chat_gpt_sumarry');
         textarea2.style.height = 'auto';
         textarea2.style.height = textarea2.scrollHeight + 'px';
         if(options.serviceLabel){
            jQuery('#servicename').text(options.serviceLabel);
         }
   }
   function filterChart(customerchange){
       jQuery('#ajax-loader').show();
        const service = $('#serviceFilter option:selected').val();
        const customer = $('#customerFilter option:selected').val();
        //reset_chart();
       jQuery('#servicename').text($('#serviceFilter option:selected').text());
        $.ajax({
            url: ajax_url,
            type: 'POST',
            dataType:'json',
            data: {
                action: 'get_skin_screening_chart',
              
                service: service,
                customer: customer
            },
            success: function(response) {
                renderReportData(response);
              jQuery('#ajax-loader').hide();
            },
            error: function(error) {
                console.error('Error fetching chart data:', error);
                jQuery('#ajax-loader').hide();
            }
        });
  }
    /**
     * Load combined report data for one or more services.
     *
     * @param {Array} services       Array of service IDs.
     * @param {string} serviceLabel  Label to display in the report header.
     * @param {Array} customers      Optional array of customer IDs to filter by.
     *                               If empty or not provided, the backend should
     *                               return data for all customers.
     */
    function loadCombinedReport(services, serviceLabel, customers, callback){
        if (!services || !services.length) {
            if (callback) callback(false);
            return;
        }

        // Normalise optional customers list: backend treats empty = all customers.
        const customerIds = Array.isArray(customers) ? customers : [];

        jQuery('#ajax-loader').show();
        jQuery('#servicename').text(serviceLabel || 'Combined Services');
        const nonceVal = (typeof ajax !== 'undefined' && ajax.nonce) ? ajax.nonce : '';
        $.ajax({
            url: ajax_url,
            type: 'POST',
            dataType:'json',
            data: {
                action: 'get_combined_group_report',
                services: services,
                customers: customerIds,
                nonce: nonceVal
            },
            success: function(response) {
                if(response && response.success && response.data){
                    renderReportData(response.data,{serviceLabel:serviceLabel || 'Combined Services'});
                    if (callback) callback(true);
                }else{
                    console.error('No combined report data',response);
                    if (callback) callback(false);
                }
                jQuery('#ajax-loader').hide();
                
                // Restore saved form data if available
                <?php if ($saved_report_data && !empty($saved_report_data['form_data'])): ?>
                const savedFormData = <?php echo wp_json_encode($saved_report_data['form_data']); ?>;
                if (savedFormData.contact_name) {
                    jQuery("input[name='contact_name']").val(savedFormData.contact_name);
                }
                if (savedFormData.phone) {
                    jQuery("input[name='phone']").val(savedFormData.phone);
                }
                if (savedFormData.details) {
                    jQuery("textarea[name='details']").val(savedFormData.details);
                }
                if (savedFormData.email) {
                    jQuery('#service_report_to_email').val(savedFormData.email);
                }
                if (savedFormData.report_email) {
                    jQuery('#report_to_email').val(savedFormData.report_email);
                }
                if (savedFormData.uploaded_file_url) {
                    jQuery('#photo_file_url').val(savedFormData.uploaded_file_url);
                    jQuery('#file_preview').html('<img src="' + savedFormData.uploaded_file_url + '" alt="Preview" class="w-100" style="max-width: 300px; margin-top: 10px;">');
                }
                if (savedFormData.chat_gpt_sumarry) {
                    jQuery('#chat_gpt_sumarry').val(savedFormData.chat_gpt_sumarry);
                }
                <?php endif; ?>
                
                // Auto-generate PDF if requested (for background generation)
                <?php if ($auto_generate_pdf): ?>
                // Hide the page body immediately to prevent it from showing
                document.body.style.display = 'none';
                document.body.style.visibility = 'hidden';
                
                setTimeout(function() {
                    // Wait for all charts to render, then trigger PDF generation
                    const pdfButton = document.getElementById('pdf_generator');
                    if (pdfButton) {
                        pdfButton.click();
                    } else if (typeof generatePDF === 'function') {
                        generatePDF('', '');
                    }
                }, 3000);
                <?php endif; ?>
            },
            error: function(error) {
                console.error('Error fetching combined chart data:', error);
                jQuery('#ajax-loader').hide();
                if (callback) callback(false);
            }
        });
    }
    // Expose setter for embedded combined preview usage
    window.setCombinedServices = function(services, label, customers, callback){
        combinedServices = (services || []).map(Number);
        combinedServiceNames = label || 'Combined Services';
        combinedCustomers = Array.isArray(customers) ? customers.map(Number) : [];
        combinedCustomerNames = ''; // currently not displayed, kept for future use

        isCombinedMode = combinedServices.length > 0;
        if (isCombinedMode) {
            jQuery('#serviceFilter').prop('disabled', true);
            jQuery('#servicename').text(combinedServiceNames);
            jQuery('#goBackBtn').hide();
            jQuery('#serviceFilterWrap').hide();
            // Pass through the customer filter so the preview matches the stats table
            loadCombinedReport(combinedServices, combinedServiceNames, combinedCustomers, callback);
        } else {
            if (callback) callback(false);
        }
    };
    // Utility: Generate Random Color
    function getRandomColor() {
        return `#${Math.floor(Math.random() * 16777215).toString(16)}`;
    }
});

</script>
<script>
    jQuery(document).ready(function ($) {
    let file_frame;
        //new uploader
    $('#upload_file_button').on('click', function (e) {
      e.preventDefault();
        $('#image_file').trigger('click'); // Trigger the hidden file input
    });
    
    // Media Library selector
    $('#select_from_media_library').on('click', function (e) {
        e.preventDefault();
        
        // If the media frame already exists, reopen it
        if (file_frame) {
            file_frame.open();
            return;
        }
        
        // Create the media frame
        file_frame = wp.media({
            title: 'Select Logo Image',
            button: {
                text: 'Use this image'
            },
            library: {
                type: 'image' // Only show images
            },
            multiple: false
        });
        
        // When an image is selected, run a callback
        file_frame.on('select', function () {
            const attachment = file_frame.state().get('selection').first().toJSON();
            $('#file_preview').html('<img src="' + attachment.url + '" alt="Preview" class="w-100" style="max-width: 300px; margin-top: 10px;">');
            $('#photo_file_url').val(attachment.url);
        });
        
        // Open the media frame
        file_frame.open();
    });
    
        $('#image_file').on('change', function () {
        jQuery('#ajax-loader').show();
        var fileInput = this;
        var file = fileInput.files[0]; // Get the selected file

        if (file) {
            var formData = new FormData();
            formData.append('action', 'amelia_custom_image_upload'); // Set custom action
            formData.append('image_file', file); // Add the file to the form data

            // Show loading message
            $('#uploading_message').show();

            // Perform AJAX request
            $.ajax({
                url: "<?php echo admin_url('admin-ajax.php'); ?>", // WordPress AJAX URL
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                      jQuery('#ajax-loader').hide();
                    // Hide loading message
                    //$('#uploading_message').hide();

                    // Handle success response
                    if (response.success) {
                        $('#file_preview').html('<img src="' + response.data.url + '" alt="Preview" style="max-width: 300px; margin-top: 10px;">');
                        $('#photo_file_url').val(response.data.url); // Store the uploaded file URL
                    } else {
                        alert('Image upload failed. Please try again.');
                    }
                },
                error: function () {
                    alert('Error uploading image.');
                    $('#uploading_message').hide();
                    jQuery('#ajax-loader').hide();
                }
            });
        }
    });
 
      //new uploader  
    $('#upload_file_button_old').on('click', function (e) {
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
//       file_frame.on('open', function() {
//        // Add a custom param to uploader
//        file_frame.uploader.options.uploader.params = {
//            prevent_size_upload: '1'
//        };
//       });
        //alert('yes');

   
        // On file selection, update the input field and preview
        file_frame.on('select', function () {
            const attachment = file_frame.state().get('selection').first().toJSON();
            $('#uploaded_file_url').val(attachment.url);
          $('#photo_file_url').val(attachment.url);
            if (attachment.type === 'image') {
                $('#file_preview').html(
                    `<img src="${attachment.url}" alt="Preview" class="w-100"   style="max-width: 300px; margin-top: 10px;">`
                );
            } else {
                $('#file_preview').html(
                    `<p>File: <a href="${attachment.url}" target="_blank">${attachment.filename}</a></p>`
                );
            }
        });

        // Open the uploader dialog
        file_frame.open();
    });
});

</script>
<!-- <script>
    const textarea = document.getElementById('chat_gpt_sumarry');

    textarea.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = this.scrollHeight + 'px';
    });
</script> -->
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
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const emailModal = document.getElementById('emailModal');
    const addEmailBtn = document.getElementById('addEmailBtn');
    const emailInput = document.getElementById('emailInput');
    const emailList = document.getElementById('emailList');
    const emailField = document.getElementById('report_to_email');
     //const emailField = document.getElementById('remove-icon');

 

    // Show the modal
       
    jQuery(document).on('click', '.remove-icon', function () {
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
 
  </script>
jQuery(document).ready(function ($) {
  function decodeHTMLEntities(text) {
    var textarea = document.createElement("textarea");
    textarea.innerHTML = text;
    return textarea.value;
  }
  //$('.select2drop').select2();

  $('.populate_description_field').on('select2:select', function (e) {
    jQuery('#ajax-loader').show();
    var selected_id = $(this).val();
    var populate_field = $(this).data('populate');
    if (selected_id) {
      $.ajax({
        url: ajax.url,
        type: 'POST',
        data: {
          action: 'fetch_description_by_id',
          id: selected_id
        },
        success: function (response) {
          $('[name="' + populate_field + '"]').val(decodeHTMLEntities(response));
          var $target = $('[name="' + populate_field + '"]');
          autoResizeTextarea($target[0]);
          jQuery('#ajax-loader').hide();
        }
      });
    } else {
      //$('#description_field').val('');
    }
  });
  function autoResizeTextarea(field) {
    field.style.height = 'auto';
    field.style.height = (field.scrollHeight) + 'px';
  }

  //id="ajax-loader" class="loader-overlay"
  // Create the loader element
  const loader = document.createElement('div');
  loader.id = 'ajax-loader';
  loader.className = 'loader-overlay';
  loader.innerHTML = '<div class="loader"></div>'; // Add your spinner or loader HTML
  // Add inline styles for the loade
  // Append the loader to the body
  //alert(loader);
  setTimeout(() => {
    jQuery('body').before(loader);
  }, 1000); // Adjust the timeout duration as needed
  jQuery(document).on('click', '#pdf_generator_popup', function () {
    const popuppdfButton2 = document.getElementById('send_popup_report');
    popuppdfButton2.removeEventListener('click', generatePDF);
    console.log('popuppdf');
    generatePDF('', 'popup');
  });
  jQuery(document).on('click', '#send_popup_report', function () {
    const popuppdfButton = document.getElementById('send_popup_report');
    popuppdfButton.removeEventListener('click', generatePDF);
    console.log('popuppdfsent');
    var toemail = jQuery('#report_to_email').val();
    if (!toemail) {
      alertify.error('Sender email not found.');
    } else {
      const emailModal = document.getElementById('customerInfoPopup');

      generatePDF(toemail, 'popup');

    }
  });
  const pdfButton = document.getElementById('pdf_generator');
  if (pdfButton) {
    document.getElementById('pdf_generator').addEventListener('click', async () => {
      var refpage = jQuery('#refpage').val();
      var bodychartpage = jQuery('#bodypage').val();
      //alert('cliecked');
      if (refpage) {
        generatePDF('', 'ref');
      }
      else if (bodychartpage) {
        generatePDF('', 'bodypage');
      }
      else {
        generatePDF('', '');
        //alert('general');
      }

      //generatePDFDevice();
    });
  }
  let cachedCanvas = null;
  async function preloadResources() {
    await Promise.all([
      document.fonts.ready, // Preload fonts
      ...Array.from(document.images).map((img) =>
        img.complete ? Promise.resolve() : new Promise((resolve) => (img.onload = img.onerror = resolve))
      ),
    ]);
    console.log("All resources loaded.");
  }
  async function generatePDF(sendEmail = false, servicepdf = false) {
    jQuery('#ajax-loader').show();
    // await preloadResources();
    console.log('generating');
    //  setTimeout(function () {
    const viewportMetaId = 'viewport-meta-admin';
    const viewportMeta = $(`#${viewportMetaId}`);
    jQuery('.pdfHtml').addClass('generating');
    
    // Force desktop viewport width for PDF generation
    const originalWidth = document.documentElement.style.width;
    document.documentElement.style.width = '1200px';
    
    if (viewportMeta.length) {
      // Remove the viewport meta tag
      viewportMeta.remove();
    }

    //  alert('yes');
    const promises = [];
    jQuery('.removefrompdf').addClass('d-none');
    const {
      jsPDF
    } = window.jspdf;

    // alert(servicepdf);
    if (servicepdf == 'popup') {
      var pdfdeviceheight = 1300;
      //jQuery('#customerInfoPopup').modal('hide');
    }
    else if (servicepdf == 'ref') {

      var pdfdeviceheight = 1200;
    }
    else {
      var pdfdeviceheight = 900;
    }
    //alert(pdfdeviceheight);

    const pdf = new jsPDF({
      orientation: "portrait",
      unit: "px",
      format: [800, pdfdeviceheight]
    }); // Fixed desktop-like dimensions });
    let yPosition = 0;
    const desktopWidth = 800;
    const desktopHeight = pdfdeviceheight;
    const textareas = document.querySelectorAll("textarea");
    // Step 1: Create temporary divs for each textarea
    textareas.forEach((textarea) => {
      const div = document.createElement("p");
      //white-space: break-spaces;
      div.style.whiteSpace = "break-spaces";
      /*div.style.whiteSpace = "pre-wrap"; // Preserve line breaks
      div.style.wordWrap = "break-word"; // Handle long words
      div.style.position = "absolute"; // Position it over the textarea
      div.style.left = textarea.offsetLeft + "px";
      div.style.top = textarea.offsetTop + "px";
      div.style.width = textarea.offsetWidth + "px";
      div.style.height = textarea.offsetHeight + "px";*/
      div.style.padding = textarea.style.padding; // Match padding
      div.style.border = textarea.style.border; // Match border style
      div.style.backgroundColor = "white"; // Match background for clarity
      /*div.style.fontSize = getComputedStyle(textarea).fontSize; // Match font size
      div.style.fontFamily = getComputedStyle(textarea).fontFamily; // Match font family
      div.style.lineHeight = getComputedStyle(textarea).lineHeight;*/
      div.textContent = textarea.value; // Copy textarea content
      textarea.style.visibility = "hidden"; // Hide the original textarea
      textarea.parentNode.insertBefore(div, textarea.nextSibling); // Insert after textarea
      textarea.style.display = 'none';
      div.classList.add("temp-div"); // Add a class for later removal

    });
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = pdf.internal.pageSize.getHeight();
    const sections = document.querySelectorAll('.pdf-capture');
    /*  sections.forEach((section) => {
       section.style.fontSize = "10px"; 
       section.style.lineHeight = "1.2";
     }); */
    const containerpdf = document.querySelectorAll('.pdf-capture .container');
    //  sections.style.width = "1200px"; // Set desktop width
    // sections.style.margin = "0 auto"; // Center for aesthetics
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    containerpdf.forEach((section) => {
      section.style.width = "1000px"; // Fixed desktop width
      section.style.margin = "0 auto"; // Center content
      //section.style.height = "1200px"; // Desktop height
    });
    const scaleFactor = 0.75;
    const margin = 20; // Set the left and right margins to 20px
    const contentWidth = pageWidth - margin * 2;
    // Function to render each section
    const renderSection = (section, index, callback) => {
      section.style.fontSize = "12px"; // Standard font size
      section.style.width = "100%";
      // section.style.maxWidth = "1200px"; // Keep the max-width fixed for desktop
      //section.style.margin = "0"; // Reset margins
      //section.style.padding = "0"; // Reset padding
      console.log('pdfgenerting');
      html2canvas(section, {
        scale: 1.5, // Use a lower scale to avoid oversized fonts
        useCORS: true, // Ensure cross-origin resources are handled
        logging: false, // Disable logging



      }).then(canvas => {
        const imgData = canvas.toDataURL('image/jpeg', 0.7);
        const imgWidth = pageWidth; // Fit to PDF width
        const imgHeight = (canvas.height * pageWidth) / canvas.width;
        if (section.querySelector('img')) {
          if (index == 4 && servicepdf == 'bodypage') {
            pdf.addImage(imgData, 'PNG', margin, 0, contentWidth, imgHeight);
            if (index < sections.length - 1) pdf.addPage();
            console.log(index);
          }
          else {
            pdf.addImage(imgData, 'PNG', margin, 0, contentWidth, pageHeight);
            if (index < sections.length - 1) pdf.addPage();
          }
          // Full-page image handling


        }
        else {
          const remainingSpace = pageHeight - yPosition;
          if (remainingSpace >= imgHeight) {
            // Center the content if footer has space
            const marginTop = (remainingSpace - imgHeight) / 2;
            pdf.addImage(imgData, 'PNG', 0, yPosition + marginTop, pageWidth, imgHeight);
            yPosition += imgHeight + marginTop;
          } else {
            // Move to a new page if not enough space
            pdf.addPage();
            yPosition = 0; // Reset Y position for the new page
            pdf.addImage(imgData, 'PNG', 0, yPosition, pageWidth, imgHeight);
            yPosition += imgHeight;
          }
          /* if (index > 0) pdf.addPage();
            pdf.addImage(imgData, "PNG", 0, 0, imgWidth, imgHeight);
*/
        }

        callback();
      });
    };
    /* for (let i = 0; i < sections.length; i++) {
         try {
             // Render each section individually
             const canvas = await html2canvas(sections[i], {
               scale: 2, // Increase resolution
            allowTaint: true, // Allow cross-origin images (if any)
           logging: true, // Debugging information
           useCORS: true
             });

            const imgData = canvas.toDataURL('image/jpeg', 0.7);
         const imgWidth = canvas.width;
         const imgHeight = canvas.height;

         // Calculate scaling factor to make content fit the PDF page
         const scaleFactor = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
         const scaledWidth = imgWidth * scaleFactor;
         const scaledHeight = imgHeight * scaleFactor;

         // Center the content within the page
         const xOffset = (pdfWidth - scaledWidth) / 2;
         const yOffset = (pdfHeight - scaledHeight) / 2;

         // Add the image to the PDF
         if (i > 0) pdf.addPage();
             pdf.addImage(imgData, 'PNG', 0, 0, 210, 0);
        // pdf.addImage(imgData, 'PNG', xOffset, yOffset, scaledWidth, scaledHeight);
         } catch (error) {
             console.error(`Error rendering section ${i + 1}:`, error);
         }
     }*/
    let index = 0;
    const renderNext = () => {
      if (index < sections.length) {
        renderSection(sections[index], index, () => {
          index++;
          renderNext();
        });
      } else {
        jQuery('.pdfHtml').removeClass('generating');
        // Restore original document width
        document.documentElement.style.width = '';
        containerpdf.forEach((section) => {
          section.style.width = ""; // Remove fixed width
          section.style.margin = ""; // Reset margin
          section.style.height = ""; // Desktop height
        });
        //pdf.save("document.pdf");
        const pdfData = pdf.output('blob');
        const pdfURL = URL.createObjectURL(pdfData);
        jQuery('.removefrompdf').removeClass('d-none');
        document.querySelectorAll(".temp-div").forEach((div) => {
          div.previousSibling.style.visibility = "visible"; // Restore textarea visibility
          div.previousSibling.style.display = "block";
          div.remove(); // Remove the temporary div
        });
        /*  if (!$(`#${viewportMetaId}`).length) {
             $('<meta>', {
                 id: viewportMetaId,
                 name: 'viewport',
                 content: 'width=device-width, initial-scale=1.0'
             }).appendTo('head');
         }*/
        if (sendEmail) {
          if (servicepdf == 'service') {
            sendServicePDFToServer(sendEmail, pdfURL, pdfData);
          } else if (servicepdf == 'ref') {
            sendRefPDFToServer(sendEmail, pdfURL, pdfData);
          }
          else if (servicepdf == 'popup') {
            sendPopPDFToServer(sendEmail, pdfURL, pdfData);
          }
          else if (servicepdf == 'meterials') {
            sendmeterialsPDFToServer(sendEmail, pdfURL, pdfData);
          }
          else {
            sendPDFToServer(sendEmail, pdfURL, pdfData);
          }
          // Custom AJAX function
        } else {

          window.open(pdfURL);
          jQuery('#ajax-loader').hide();
        }
      }
    };

    renderNext();



    //}, 2000);
  }
  async function generatePDFDevice(sendEmail = false, servicepdf = false) {
    jQuery('#ajax-loader').show();
    //  alert('yes');
    const promises = [];
    const sections = document.querySelectorAll(".pdf-capture");

    // Initialize jsPDF
    //const pdf = new jsPDF("p", "mm", "a4"); // Portrait mode, millimeters, A4 size


    jQuery('.removefrompdf').addClass('d-none');
    const {
      jsPDF
    } = window.jspdf;
    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
    });
    const textareas = document.querySelectorAll("textarea");

    // Step 1: Create temporary divs for each textarea
    textareas.forEach((textarea) => {
      const div = document.createElement("div");
      div.style.whiteSpace = "pre-wrap"; // Preserve line breaks
      div.style.wordWrap = "break-word"; // Handle long words
      div.style.position = "absolute"; // Position it over the textarea
      div.style.left = textarea.offsetLeft + "px";
      div.style.top = textarea.offsetTop + "px";
      div.style.width = textarea.offsetWidth + "px";
      div.style.height = textarea.offsetHeight + "px";
      div.style.padding = textarea.style.padding; // Match padding
      div.style.border = textarea.style.border; // Match border style
      div.style.backgroundColor = "white"; // Match background for clarity
      div.style.fontSize = getComputedStyle(textarea).fontSize; // Match font size
      div.style.fontFamily = getComputedStyle(textarea).fontFamily; // Match font family
      div.style.lineHeight = getComputedStyle(textarea).lineHeight;
      div.textContent = textarea.value; // Copy textarea content
      textarea.style.visibility = "hidden"; // Hide the original textarea
      textarea.parentNode.insertBefore(div, textarea.nextSibling); // Insert after textarea
      div.classList.add("temp-div"); // Add a class for later removal
    });
    //  const pdfWidth = pdf.internal.pageSize.getWidth();
    //  const pdfHeight = pdf.internal.pageSize.getHeight();
    //const sections = document.querySelectorAll('.pdf-capture');
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();


    sections.forEach((section, index) => {
      promises.push(
        html2canvas(section, {
          scale: 2, // Higher scale for better quality
          useCORS: true, // Handle cross-origin images
        }).then((canvas) => {
          const imgData = canvas.toDataURL('image/jpeg', 0.7);
          const imgWidth = pageWidth;
          const imgHeight = (canvas.height * imgWidth) / canvas.width;

          if (imgHeight > pageHeight) {
            console.warn(
              `Section ${index + 1} is too tall for one page. Consider splitting it.`
            );
          }

          if (index > 0) {
            pdf.addPage(); // Add new page for subsequent sections
          }

          // Add the rendered section to the PDF
          pdf.addImage(imgData, "PNG", 0, 0, imgWidth, imgHeight);
        })
      );
    });

    // After all sections are processed, save the PDF
    Promise.all(promises).then(() => {
      const pdfData = pdf.output('blob');
      const pdfURL = URL.createObjectURL(pdfData);
      jQuery('.removefrompdf').removeClass('d-none');
      document.querySelectorAll(".temp-div").forEach((div) => {
        div.previousSibling.style.visibility = "visible"; // Restore textarea visibility
        div.remove(); // Remove the temporary div
      });
      if (sendEmail) {
        if (servicepdf == 'service') {
          sendServicePDFToServer(sendEmail, pdfURL, pdfData);
        } else if (servicepdf == 'ref') {
          sendRefPDFToServer(sendEmail, pdfURL, pdfData);
        } else {
          sendPDFToServer(sendEmail, pdfURL, pdfData);
        }
        // Custom AJAX function
      } else {

        window.open(pdfURL);
        jQuery('#ajax-loader').hide();
      }
    });

    //renderNext();




  }

  function sendRefPDFToServer(sendEmail, pdfurl, pdfData) {
    //alert(sendEmail);
    var formData = new FormData();
    formData.append('pdf', pdfData, 'document.pdf');
    formData.append('action', 'ref_pdf_sent_user');
    formData.append('toemail', sendEmail);


    jQuery.ajax({
      url: ajax.url,
      dataType: 'html',
      contentType: false,
      processData: false,
      type: 'POST',
      data: formData,
      success: function (response) {

        jQuery('#ajax-loader').hide();
        if (response == 'Success') {
          alertify.notify('Successfully Sent!', 'success', 5, function () {

          });
        } else {
          alertify.notify(response, 'error', 5, function () {
            //console.log('Notification closed');
          });
        }


        body_chart_ref_data_ajax(sendEmail);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        jQuery('#ajax-loader').hide();
        alertify.notify(response, 'error', 5, function () {
          console.log('Notification closed');
        });
      }
    });
  }

  function sendServicePDFToServer(sendEmail, pdfurl, pdfData) {


    //alert(sendEmail);
    var formData = new FormData();
    formData.append('pdf', pdfData, 'document.pdf');
    formData.append('action', 'service_pdf_sent_user');
    formData.append('toemail', sendEmail);
    formData.append('name', jQuery("input[name='contact_name']").val());
    formData.append('service_id', jQuery('#serviceFilter option:selected').val());
    formData.append('service_name', jQuery('#serviceFilter option:selected').text());
    jQuery.ajax({
      url: ajax.url,
      dataType: 'html',
      contentType: false,
      processData: false,
      type: 'POST',
      data: formData,
      success: function (response) {

        jQuery('#ajax-loader').hide();
        if (response == 'Success') {
          alertify.notify('Successfully Sent!', 'success', 5, function () {

          });
        } else {
          alertify.notify(response, 'error', 5, function () {
            //console.log('Notification closed');
          });
        }


        service_chart_data_ajax(sendEmail);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        jQuery('#ajax-loader').hide();
        alertify.notify(response, 'error', 5, function () {
          console.log('Notification closed');
        });
      }
    });



  }
  function sendmeterialsPDFToServer(sendEmail, pdfurl, pdfData) {


    //alert(sendEmail);
    var formData = new FormData();
    formData.append('pdf', pdfData, 'document.pdf');
    formData.append('action', 'meterials_pdf_sent_user');
    formData.append('toemail', sendEmail);
    formData.append('name', jQuery("input[name='contact_name']").val());
    formData.append('service_id', jQuery('#serviceFilter option:selected').val());
    formData.append('service_name', jQuery('#serviceFilter option:selected').text());
    jQuery.ajax({
      url: ajax.url,
      dataType: 'html',
      contentType: false,
      processData: false,
      type: 'POST',
      data: formData,
      success: function (response) {

        jQuery('#ajax-loader').hide();
        if (response == 'Success') {
          alertify.notify('Successfully Sent!', 'success', 5, function () {

          });
        } else {
          alertify.notify(response, 'error', 5, function () {
            //console.log('Notification closed');
          });
        }


        save_program_meterials_data_ajax(sendEmail);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        jQuery('#ajax-loader').hide();
        alertify.notify(response, 'error', 5, function () {
          console.log('Notification closed');
        });
      }
    });



  }

  function sendPDFToServer(sendEmail, pdfurl, pdfData) {


    //alert(sendEmail);
    var formData = new FormData();
    formData.append('pdf', pdfData, 'document.pdf');
    formData.append('action', 'pdf_sent_user');
    formData.append('toemail', sendEmail);
    formData.append('appointment_id', jQuery('#appoinment_id').val());
    formData.append('name', jQuery("input[name='name']").val());
    jQuery.ajax({
      url: ajax.url,
      dataType: 'html',
      contentType: false,
      processData: false,
      type: 'POST',
      data: formData,
      success: function (response) {
        var popup = jQuery('#customerInfoPopup').length;
        if (popup) {

          // jQuery('#customerInfoPopup').modal('hide');
        }
        jQuery('#ajax-loader').hide();
        if (response == 'Success') {
          alertify.notify('Successfully Sent!', 'success', 5, function () {

          });
        } else {
          alertify.notify(response, 'error', 5, function () {
            //console.log('Notification closed');
          });
        }

        //if(!savefalse){
        body_chart_data_ajax(sendEmail);
        //}

      },
      error: function (jqXHR, textStatus, errorThrown) {
        jQuery('#ajax-loader').hide();
        alertify.notify(response, 'error', 5, function () {
          console.log('Notification closed');
        });
      }
    });



  }
  function sendPopPDFToServer(sendEmail, pdfurl, pdfData) {


    //alert(sendEmail);
    var formData = new FormData();
    formData.append('pdf', pdfData, 'document.pdf');
    formData.append('action', 'popup_pdf_sent_user');
    formData.append('toemail', sendEmail);
    formData.append('appointment_id', jQuery('#appoinment_id').val());
    formData.append('name', jQuery("input[name='name']").val());
    jQuery.ajax({
      url: ajax.url,
      dataType: 'html',
      contentType: false,
      processData: false,
      type: 'POST',
      data: formData,
      success: function (response) {
        var popup = jQuery('#customerInfoPopup').length;
        if (popup) {

          // jQuery('#customerInfoPopup').modal('hide');
        }
        jQuery('#ajax-loader').hide();
        if (response == 'Success') {
          alertify.notify('Successfully Sent!', 'success', 5, function () {

          });
        } else {
          alertify.notify(response, 'error', 5, function () {
            //console.log('Notification closed');
          });
        }

        //if(!savefalse){
        body_chart_data_ajax(sendEmail);
        //}

      },
      error: function (jqXHR, textStatus, errorThrown) {
        jQuery('#ajax-loader').hide();
        alertify.notify(response, 'error', 5, function () {
          console.log('Notification closed');
        });
      }
    });



  }

  function base64ToBlob(base64, mimeType) {
    var byteCharacters = atob(base64);
    var byteNumbers = new Array(byteCharacters.length);
    for (var i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    var byteArray = new Uint8Array(byteNumbers);
    return new Blob([byteArray], {
      type: mimeType
    });
  }


  function body_chart_data_ajax(toemail = '') {


    var appoinment_id = jQuery('#appoinment_id').val();
    if (appoinment_id) {
      if (!toemail) {
        jQuery('#ajax-loader').show();
      }
      $.ajax({
        url: ajax.url, // AJAX handler
        type: 'POST',
        data: {
          action: 'save_body_chart_action', // Action name
          formdata: jQuery("#body_chart_form").serialize(),
          appoinment_id: appoinment_id,
          toemail: toemail,
          nonce: ajax.nonce
        },
        success: function (response) {
          jQuery('#ajax-loader').hide();
          var alertDiv = document.getElementById('alert-message');
          alertify.notify(response, 'success', 5, function () {
            console.log('Notification closed');
          });

        },
        error: function () {
          jQuery('#ajax-loader').hide();
        }
      });
    }

  }
  function save_program_meterials_data_ajax(toemail = '') {

    var service_id = jQuery('#serviceFilter option:selected').val();

    if (service_id) {
      if (!toemail) {
        jQuery('#ajax-loader').show();
      }
      $.ajax({
        url: ajax.url, // AJAX handler
        type: 'POST',
        data: {
          action: 'save_program_meterials_action', // Action name
          formdata: jQuery("#meterial_form").serialize(),
          service_id: service_id,
          toemail: toemail,
          nonce: ajax.nonce
        },
        success: function (response) {
          jQuery('#ajax-loader').hide();
          var alertDiv = document.getElementById('alert-message');
          alertify.notify(response, 'success', 5, function () {
            console.log('Notification closed');
          });

        },
        error: function () {
          jQuery('#ajax-loader').hide();
        }
      });
    }
    else {
      alertify.notify('Please select a service', 'error', 5, function () {
        console.log('Notification closed');
      });
    }

  }

  function body_chart_ref_data_ajax(toemail = '') {
    if (!toemail) {
      jQuery('#ajax-loader').show();
    }

    var appoinment_id = jQuery('#appoinment_id').val();
    $.ajax({
      url: ajax.url, // AJAX handler
      type: 'POST',
      data: {
        action: 'save_body_chart_ref_action', // Action name
        formdata: jQuery("#body_chart_ref_form").serialize(),
        appoinment_id: appoinment_id,
        toemail: toemail,
        nonce: ajax.nonce
      },
      success: function (response) {
        jQuery('#ajax-loader').hide();
        var alertDiv = document.getElementById('alert-message');
        alertify.notify(response, 'success', 5, function () {
          console.log('Notification closed');
        });

      },
      error: function () {
        jQuery('#ajax-loader').hide();
      }
    });
  }

  function service_chart_data_ajax(toemail = '') {
    if (!toemail) {
      jQuery('#ajax-loader').show();
    }

    var service_id = jQuery('#serviceFilter option:selected').val();

    if (service_id) {
      $.ajax({
        url: ajax.url, // AJAX handler
        type: 'POST',
        data: {
          action: 'save_service_chart_action', // Action name
          formdata: jQuery("#service_chart_form").serialize(),
          service_id: service_id,
          toemail: toemail,
          nonce: ajax.nonce
        },
        success: function (response) {
          jQuery('#ajax-loader').hide();
          var alertDiv = document.getElementById('alert-message');
          alertify.notify(response, 'success', 5, function () {
            console.log('Notification closed');
          });

        },
        error: function () {
          jQuery('#ajax-loader').hide();
        }
      });
    }
    else {
      alertify.notify('Please select a service', 'error', 5, function () {
        console.log('Notification closed');
      });
    }
  }
  $('#save_send_email').on('click', function (e) {
    var toemail = jQuery('#report_to_email').val();
    if (!toemail) {
      alertify.error('Sender email not found.');
    } else {
      const emailModal = document.getElementById('emailModal');
      emailModal.style.display = 'none';
      generatePDF(toemail);
    }

  });
  $('#save_send_program_email').on('click', function (e) {
    var toemail = jQuery('#report_to_email').val();
    if (!toemail) {
      alertify.error('Sender email not found.');
    } else {
      const emailModal = document.getElementById('emailModal');
      emailModal.style.display = 'none';
      generatePDF(toemail, 'meterials');
    }

  });
  $('#save_send_email_ref').on('click', function (e) {
    var toemail = jQuery('#report_to_email').val();
    if (!toemail) {
      alertify.error('Sender email not found.');
    } else {
      const emailModal = document.getElementById('emailModal');
      emailModal.style.display = 'none';
      generatePDF(toemail, 'ref');
    }

  });
  $('#save_send_service_email').on('click', function (e) {
    var toemail = jQuery('#report_to_email').val();
    if (!toemail) {
      alertify.error('Sender email not found.');
    } else {
      const emailModal = document.getElementById('emailModal');
      emailModal.style.display = 'none';
      generatePDF(toemail, 'service');
    }

  });
  jQuery('#ref_btn').on('click', function (e) {
    e.preventDefault(); // Prevent the default action of the link

    const link = e.target.href; // Store the href value
    body_chart_data_ajax('');
    window.location.href = link;
  });
  // Listen for the click event on the button
  $('#save_body_chart').on('click', function (e) {
    e.preventDefault();
    body_chart_data_ajax('');
  });
  //meterial_form
  $('#save_program_meterials').on('click', function (e) {
    e.preventDefault();
    save_program_meterials_data_ajax('');
  });
  $('#save_body_chart_ref').on('click', function (e) {
    e.preventDefault();
    body_chart_ref_data_ajax('');
  });
  $('#save_service_chart').on('click', function (e) {
    e.preventDefault();
    service_chart_data_ajax('');
  });

  // Combined group report (multi-service) from settings page
  const combinedServiceSelect = $('#combined_services_select');
  const combinedCustomersSelect = $('#combined_customers_select');
  const combinedReportTable = $('#combined-report-table');
  const combinedReportMessage = $('#combined-report-message');
  const combinedReportSummary = $('#combined-report-summary');

  // Initialize Select2 for customer dropdown if it exists
  if (combinedCustomersSelect.length) {
    combinedCustomersSelect.select2({
      placeholder: 'Search and select customers...',
      allowClear: true,
      width: '100%'
    });
  }
  const combinedPreviewInline = $('#combined-preview-inline');

  function renderCombinedReport(data) {
    if (!combinedReportTable.length) return;

    const referrals = data.referal || {};
    const outdoor = data.outdoordata || {};
    const sunscreen = data.sunscreen || {};

    $('#combined-total-participants').text(data.totalparticipent || 0);
    $('#combined-total-attended').text(data.totalattended || 0);
    $('#combined-referral-immediate').text(referrals.imidiate || 0);
    $('#combined-referral-month').text(referrals.month || 0);
    $('#combined-outdoor-yes').text(outdoor['Yes'] ? outdoor['Yes'] : 0);
    $('#combined-sunscreen-yes').text(sunscreen['Yes'] ? sunscreen['Yes'] : 0);

    if (data.chatGptSumarry) {
      combinedReportSummary.text(data.chatGptSumarry).show();
    } else {
      combinedReportSummary.hide();
    }

    combinedReportTable.show();
  }

  $('#generate_combined_report').on('click', function (e) {
    if (!combinedServiceSelect.length) return;
    e.preventDefault();

    const selectedServices = combinedServiceSelect.val() || [];
    const selectedCustomers = jQuery('#combined_customers_select').val() || [];
    combinedReportMessage.removeClass('notice-error notice-success').hide();

    if (!selectedServices.length) {
      alertify.notify('Please select at least one service', 'error', 5);
      return;
    }

    jQuery('#ajax-loader').show();
    $.ajax({
      url: ajax.url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'get_combined_group_report',
        services: selectedServices,
        customers: selectedCustomers,
        nonce: ajax.nonce
      },
      success: function (response) {
        jQuery('#ajax-loader').hide();
        if (response && response.success && response.data) {
          renderCombinedReport(response.data);
          combinedReportMessage.addClass('notice-success').text('Combined report generated.').show();
          if (combinedPreviewInline.length && typeof window.setCombinedServices === 'function') {
            // Build a readable comma-separated list of selected service names
            const selectedNames = combinedServiceSelect
              .find('option:selected')
              .map(function () {
                return $(this).text();
              })
              .get()
              .join(', ');

            // Show the inline preview container and pass both services and customers
            combinedPreviewInline.show();
            window.setCombinedServices(
              selectedServices,
              selectedNames || 'Combined Services',
              selectedCustomers // ensures preview is filtered to the same customers as the table
            );
          }
        } else {
          const message = (response && response.data) ? response.data : 'Unable to generate the combined report.';
          combinedReportMessage.addClass('notice-error').text(message).show();
          combinedReportTable.hide();
          combinedReportSummary.hide();
          if (combinedPreviewInline.length) combinedPreviewInline.hide();
        }
      },
      error: function () {
        jQuery('#ajax-loader').hide();
        combinedReportMessage.addClass('notice-error').text('Something went wrong while generating the combined report.').show();
        combinedReportTable.hide();
        combinedReportSummary.hide();
        if (combinedPreviewInline.length) combinedPreviewInline.hide();
      }
    });
  });

  // Saved Combined Reports functionality
  let currentSavedReportsPage = 1;
  let currentSavedReportsSearch = '';

  // Load saved reports on page load
  if (jQuery('#saved-reports-list').length) {
    loadSavedReports();
  }

  // Search saved reports
  jQuery('#search-saved-reports').on('click', function () {
    currentSavedReportsSearch = jQuery('#saved-reports-search').val();
    currentSavedReportsPage = 1;
    loadSavedReports();
  });

  // Clear search
  jQuery('#clear-saved-reports-search').on('click', function () {
    jQuery('#saved-reports-search').val('');
    currentSavedReportsSearch = '';
    currentSavedReportsPage = 1;
    loadSavedReports();
  });

  // Enter key search
  jQuery('#saved-reports-search').on('keypress', function (e) {
    if (e.which === 13) {
      e.preventDefault();
      jQuery('#search-saved-reports').click();
    }
  });

  function loadSavedReports() {
    const savedReportsList = jQuery('#saved-reports-list');
    const savedReportsLoading = jQuery('#saved-reports-loading');
    const savedReportsPagination = jQuery('#saved-reports-pagination');

    savedReportsLoading.show();
    savedReportsList.hide();

    jQuery.ajax({
      url: ajax.url,
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_saved_combined_reports',
        nonce: ajax.nonce,
        page: currentSavedReportsPage,
        per_page: 20,
        search: currentSavedReportsSearch
      },
      success: function (response) {
        savedReportsLoading.hide();
        if (response && response.success && response.data) {
          renderSavedReportsList(response.data.reports);
          renderSavedReportsPagination(response.data);
        } else {
          savedReportsList.html('<p>No saved reports found.</p>').show();
        }
      },
      error: function () {
        savedReportsLoading.hide();
        savedReportsList.html('<p class="error">Error loading saved reports.</p>').show();
      }
    });
  }

  function renderSavedReportsList(reports) {
    const savedReportsList = jQuery('#saved-reports-list');

    if (!reports || reports.length === 0) {
      savedReportsList.html('<p>No saved reports found.</p>').show();
      return;
    }

    let html = '<table class="widefat striped" style="margin-top: 10px;"><thead><tr>';
    html += '<th>Report Name</th><th>Services</th><th>Total Customers</th><th>Created By</th><th>Created Date</th><th>Actions</th>';
    html += '</tr></thead><tbody>';

    reports.forEach(function (report) {
      const createdDate = new Date(report.created_at).toLocaleDateString();
      const serviceNames = report.service_names || 'N/A';
      const customerIds = report.customer_ids || [];
      const customerNames = report.customer_names || '';
      const totalCustomers = customerIds.length > 0 ? customerIds.length : (customerNames ? customerNames.split(',').length : 0);

      html += '<tr>';
      html += '<td><strong>' + (report.report_name || 'Unnamed Report') + '</strong></td>';
      html += '<td>' + serviceNames + '</td>';
      html += '<td>';
      if (totalCustomers > 0) {
        html += totalCustomers;
      } else {
        html += 'None';
      }
      html += '</td>';
      html += '<td>' + (report.created_by_name || 'Unknown') + '</td>';
      html += '<td>' + createdDate + '</td>';
      html += '<td>';
      html += '<button class="button view-saved-report" data-report-id="' + report.id + '" style="margin-right: 5px;">View</button>';
      html += '<button class="button download-saved-report" data-report-id="' + report.id + '" style="margin-right: 5px;">Download PDF</button>';
      if (totalCustomers > 0) {
        const encodedNames = customerNames.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        html += '<button class="button view-customer-list" data-report-id="' + report.id + '" data-customer-names="' + encodedNames + '" style="margin-right: 5px;">View Customer List</button>';
      }
      html += '<button class="button delete-saved-report" data-report-id="' + report.id + '" style="color: #dc3232;">Delete</button>';
      html += '</td>';
      html += '</tr>';
    });

    html += '</tbody></table>';
    savedReportsList.html(html).show();

    // Attach event handlers
    jQuery('.view-saved-report').on('click', function () {
      const reportId = jQuery(this).data('report-id');
      viewSavedReport(reportId);
    });

    jQuery('.download-saved-report').on('click', function () {
      const reportId = jQuery(this).data('report-id');
      downloadSavedReport(reportId);
    });

    jQuery('.view-customer-list').on('click', function () {
      let customerNames = jQuery(this).data('customer-names') || '';
      // Decode HTML entities
      customerNames = customerNames.replace(/&quot;/g, '"').replace(/&#39;/g, "'");
      showCustomerListModal(customerNames);
    });

    jQuery('.delete-saved-report').on('click', function () {
      if (confirm('Are you sure you want to delete this report?')) {
        const reportId = jQuery(this).data('report-id');
        deleteSavedReport(reportId);
      }
    });
  }

  function renderSavedReportsPagination(data) {
    const savedReportsPagination = jQuery('#saved-reports-pagination');

    if (data.total_pages <= 1) {
      savedReportsPagination.html('');
      return;
    }

    let html = '<div style="text-align: center;">';

    if (data.page > 1) {
      html += '<button class="button" data-page="' + (data.page - 1) + '">Previous</button> ';
    }

    html += '<span style="margin: 0 10px;">Page ' + data.page + ' of ' + data.total_pages + '</span>';

    if (data.page < data.total_pages) {
      html += ' <button class="button" data-page="' + (data.page + 1) + '">Next</button>';
    }

    html += '</div>';
    savedReportsPagination.html(html);

    savedReportsPagination.find('button').on('click', function () {
      currentSavedReportsPage = parseInt(jQuery(this).data('page'));
      loadSavedReports();
    });
  }

  function viewSavedReport(reportId) {
    jQuery.ajax({
      url: ajax.url,
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_saved_combined_reports',
        nonce: ajax.nonce,
        report_id: reportId
      },
      success: function (response) {
        if (response && response.success && response.data && response.data.reports.length > 0) {
          const report = response.data.reports[0];
          const serviceIds = report.service_ids || [];
          const customerIds = report.customer_ids || [];

          // Set services in select
          jQuery('#combined_services_select').val(serviceIds).trigger('change');

          // Set customers in select if available
          if (customerIds && customerIds.length > 0) {
            jQuery('#combined_customers_select').val(customerIds).trigger('change.select2');
          } else {
            jQuery('#combined_customers_select').val(null).trigger('change.select2');
          }

          // Restore form data if available
          if (report.form_data) {
            const formData = typeof report.form_data === 'string' ? JSON.parse(report.form_data) : report.form_data;

            if (formData.contact_name) {
              jQuery("input[name='contact_name']").val(formData.contact_name);
            }
            if (formData.phone) {
              jQuery("input[name='phone']").val(formData.phone);
            }
            if (formData.details) {
              jQuery("textarea[name='details']").val(formData.details);
            }
            if (formData.email) {
              jQuery('#service_report_to_email').val(formData.email);
            }
            if (formData.report_email) {
              jQuery('#report_to_email').val(formData.report_email);
            }
            if (formData.uploaded_file_url) {
              jQuery('#photo_file_url').val(formData.uploaded_file_url);
              jQuery('#file_preview').html('<img src="' + formData.uploaded_file_url + '" alt="Preview" class="w-100" style="max-width: 300px; margin-top: 10px;">');
            }
            if (formData.chat_gpt_sumarry) {
              jQuery('#chat_gpt_sumarry').val(formData.chat_gpt_sumarry);
            }
          }

          // Generate report
          setTimeout(function () {
            jQuery('#generate_combined_report').click();
          }, 500);
        }
      }
    });
  }

  function downloadSavedReport(reportId) {
    // Prevent multiple simultaneous downloads
    if (window.isGeneratingPDF) {
      alertify.notify('PDF generation already in progress. Please wait...', 'info', 5);
      return;
    }

    // Get saved report data, then load it into the inline combined preview
    // and trigger the same PDF generation flow as the "PDF Generate" button.
    jQuery.ajax({
      url: ajax.url,
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_saved_combined_reports',
        nonce: ajax.nonce,
        report_id: reportId
      },
      success: function (response) {
        if (response && response.success && response.data && response.data.reports.length > 0) {
          const report = response.data.reports[0];
          const serviceIds = report.service_ids || [];
          const customerIds = report.customer_ids || [];
          const serviceNames = report.service_names || 'Combined Services';

          if (serviceIds.length === 0) {
            alertify.notify('No services found in report', 'error', 5);
            return;
          }

          // Ensure the inline preview container is visible
          const previewContainer = jQuery('#combined-preview-inline');
          if (previewContainer.length) {
            previewContainer.show();
          }

          // Use the same code path as when the user manually generates a combined report:
          // this will load charts and summary into the embedded preview.
          if (typeof window.setCombinedServices === 'function') {
            window.isGeneratingPDF = true;
            jQuery('#ajax-loader').show();

            // Pass a callback that will be called when data is loaded
            window.setCombinedServices(
              serviceIds,
              serviceNames || 'Combined Services',
              customerIds || [],
              function (success) {
                if (!success) {
                  window.isGeneratingPDF = false;
                  jQuery('#ajax-loader').hide();
                  alertify.notify('Failed to load report data', 'error', 5);
                  return;
                }

                // Wait a bit for charts to render, then trigger PDF generation
                setTimeout(function () {
                  const pdfButton = document.getElementById('pdf_generator');
                  if (pdfButton) {
                    pdfButton.click();
                    // Reset flag after a delay to allow PDF generation to complete
                    setTimeout(function () {
                      window.isGeneratingPDF = false;
                    }, 1000);
                  } else if (typeof generatePDF === 'function') {
                    // Fallback: call generator directly if button is not found
                    generatePDF('', '');
                    setTimeout(function () {
                      window.isGeneratingPDF = false;
                    }, 1000);
                  } else {
                    window.isGeneratingPDF = false;
                    jQuery('#ajax-loader').hide();
                    alertify.notify('Unable to generate PDF for this report.', 'error', 5);
                  }
                }, 2000); // allow time for charts to render
              }
            );
          } else {
            alertify.notify('Preview is not available to generate PDF.', 'error', 5);
          }
        } else {
          alertify.notify('Failed to load report', 'error', 5);
        }
      },
      error: function () {
        alertify.notify('Error loading report', 'error', 5);
      }
    });
  }

  function showCustomerListModal(customerNames) {
    if (!customerNames || customerNames.trim() === '') {
      alertify.notify('No customers selected for this report', 'info', 5);
      return;
    }

    // Split customer names (they are stored as comma-separated)
    const customers = customerNames.split(',').map(function (name) {
      return name.trim();
    }).filter(function (name) {
      return name.length > 0;
    });

    // Create modal HTML
    let modalHtml = '<div id="customer-list-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000; justify-content: center; align-items: center;">';
    modalHtml += '<div style="background: white; padding: 20px; border-radius: 8px; max-width: 600px; max-height: 80vh; overflow-y: auto; position: relative;">';
    modalHtml += '<button id="close-customer-modal" style="position: absolute; top: 10px; right: 10px; background: #dc3232; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 18px; line-height: 1;">×</button>';
    modalHtml += '<h2 style="margin-top: 0;">Customer List</h2>';
    modalHtml += '<p><strong>Total Customers: ' + customers.length + '</strong></p>';
    modalHtml += '<ul style="list-style: none; padding: 0; margin: 0;">';
    customers.forEach(function (customer, index) {
      modalHtml += '<li style="padding: 8px; border-bottom: 1px solid #eee;">' + (index + 1) + '. ' + customer + '</li>';
    });
    modalHtml += '</ul>';
    modalHtml += '</div>';
    modalHtml += '</div>';

    // Remove existing modal if any
    jQuery('#customer-list-modal').remove();

    // Add modal to body
    jQuery('body').append(modalHtml);

    // Show modal
    jQuery('#customer-list-modal').css('display', 'flex');

    // Close modal handlers
    jQuery('#close-customer-modal, #customer-list-modal').on('click', function (e) {
      if (e.target === this || jQuery(e.target).attr('id') === 'close-customer-modal') {
        jQuery('#customer-list-modal').remove();
      }
    });
  }

  function deleteSavedReport(reportId) {
    jQuery.ajax({
      url: ajax.url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'delete_saved_combined_report',
        nonce: ajax.nonce,
        report_id: reportId
      },
      success: function (response) {
        if (response && response.success) {
          alertify.notify('Report deleted successfully', 'success', 5);
          loadSavedReports();
        } else {
          alertify.notify('Failed to delete report', 'error', 5);
        }
      },
      error: function () {
        alertify.notify('Error deleting report', 'error', 5);
      }
    });
  }

  // Modify save button for combined reports
  jQuery(document).on('click', '#save_service_chart', function (e) {
    // Check if we're in combined mode
    const combinedPreviewInline = jQuery('#combined-preview-inline');
    if (combinedPreviewInline.length && combinedPreviewInline.is(':visible')) {
      e.preventDefault();

      // Get current report data
      const selectedServices = jQuery('#combined_services_select').val() || [];
      if (selectedServices.length === 0) {
        alertify.notify('Please select services first', 'error', 5);
        return;
      }

      // Get service names
      const serviceNames = [];
      jQuery('#combined_services_select option:selected').each(function () {
        serviceNames.push(jQuery(this).text());
      });

      // Get selected customers
      const selectedCustomers = jQuery('#combined_customers_select').val() || [];
      const customerNames = [];
      jQuery('#combined_customers_select option:selected').each(function () {
        customerNames.push(jQuery(this).text());
      });

      // Prompt for report name
      const reportName = prompt('Enter a name for this report:');
      if (!reportName || reportName.trim() === '') {
        return;
      }

      // Get report data from the preview
      const reportData = {
        totalparticipent: jQuery('#totalparticipent').text() || 0,
        totalattended: jQuery('#totalattended').text() || 0,
        referal: {
          imidiate: jQuery('#immediateReferrals').text() || 0,
          month: jQuery('#monthReferrals').text() || 0
        },
        chatGptSumarry: jQuery('#chat_gpt_sumarry').val() || ''
      };

      // Get all form data (contact info, address, email, photo, etc.)
      const formData = {
        contact_name: jQuery("input[name='contact_name']").val() || '',
        phone: jQuery("input[name='phone']").val() || '',
        details: jQuery("textarea[name='details']").val() || '',
        email: jQuery('#service_report_to_email').val() || '',
        report_email: jQuery('#report_to_email').val() || '',
        uploaded_file_url: jQuery('#photo_file_url').val() || '',
        chat_gpt_sumarry: jQuery('#chat_gpt_sumarry').val() || ''
      };

      // Show loading screen
      jQuery('#ajax-loader').show();

      // Save the report
      jQuery.ajax({
        url: ajax.url,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'save_combined_report',
          nonce: ajax.nonce,
          report_name: reportName.trim(),
          service_ids: selectedServices,
          service_names: serviceNames.join(', '),
          customer_ids: selectedCustomers,
          customer_names: customerNames.join(', '),
          coupon_codes: '',
          report_data: reportData,
          contact_name: formData.contact_name,
          phone: formData.phone,
          details: formData.details,
          email: formData.email,
          report_email: formData.report_email,
          uploaded_file_url: formData.uploaded_file_url,
          chat_gpt_sumarry: formData.chat_gpt_sumarry
        },
        success: function (response) {
          jQuery('#ajax-loader').hide();
          if (response && response.success) {
            alertify.notify('Report saved successfully', 'success', 5);
            loadSavedReports();
          } else {
            alertify.notify('Failed to save report: ' + (response.data || 'Unknown error'), 'error', 5);
          }
        },
        error: function (xhr, status, error) {
          jQuery('#ajax-loader').hide();
          const errorMessage = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : 'Error saving report';
          alertify.notify('Error saving report: ' + errorMessage, 'error', 5);
        }
      });
    }
  });

  //view history
  var FORM_SELECTOR = '.el-form.demo-form-inline';
  var DATE_CONTAINER = '.popover-container';
  var DATE_INPUT_SELECTOR = DATE_CONTAINER + ' input.el-input__inner[type="text"]';
  var FIRST_TEXT_SELECTOR = 'input.el-input__inner[type="text"]';

  function fireAll(el) {
    try {
      var setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
      setter && setter.call(el, el.value);
    } catch (_) { }
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    $(el).trigger('input').trigger('change');
  }

  $(document).on('click', '.view_history', function (event) {
    var $form = $(FORM_SELECTOR).first();
    var parentRow = $(this).closest('.am-appointment-data');
    var button = event.target;
    var titlearea = parentRow.find('h3');
    var HISTORY_TEXT = titlearea.find('span').text().replace(/\s+/g, ' ').trim();

    if ($form.length === 0) return;

    var $date = $form.find(DATE_INPUT_SELECTOR).first();
    if ($date.length) {
      try {
        var setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
        if (setter) setter.call($date[0], '');
        else $date.val('');
      } catch (_) { $date.val(''); }
      fireAll($date[0]);
    }

    var $firstText = $form
      .find(FIRST_TEXT_SELECTOR)
      .filter(function () { return $(this).closest(DATE_CONTAINER).length === 0; })
      .first();

    if ($firstText.length) {
      try {
        var setter2 = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
        if (setter2) setter2.call($firstText[0], String(HISTORY_TEXT));
        else $firstText.val(String(HISTORY_TEXT));
      } catch (_) { $firstText.val(String(HISTORY_TEXT)); }
      fireAll($firstText[0]);

      $firstText.trigger('focus');
      jQuery('.am-v-date-picker-suffix').trigger('click');
    }
  });
  //view history
  $(document).on("click", ".view_appoinment", function (event) {
    jQuery('#ajax-loader').show();
    // Find the parent row of the clicked button
    var parentRow = $(this).closest('.am-appointment-data');
    var button = event.target;
    // Find the checkbox inside the parent row
    var checkbox = parentRow.find('.am-appointment-checkbox');
    console.log(checkbox);
    // Get the checkbox value if it's checked
    var checkboxValue = checkbox.find('.el-checkbox__original').val();
    if (!checkboxValue) {
      checkboxValue = $(this).data('id');
    }
    // Log the value or do something with it
    console.log('Checkbox value:', checkboxValue);
    jQuery('.am-appointment-details').addClass('d-none');
    // Optionally, display it somewhere on the page or perform further actions
    //alert('Checkbox value: ' + checkboxValue);
    fetchAppoinmentModalContent(button, checkboxValue);
  });
});

function fetchAppoinmentModalContent(button, id) {
  button.disabled = true;
  button.innerText = 'Loading...';
  // Perform AJAX request
  fetch('' + ajax.url + '?action=load_appointment_modal_content&id=' + id + '', {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json'
    }
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('Failed to load modal content');
      }
      return response.text(); // Expect HTML content
    })
    .then(data => {
      // Create modal container dynamically
      var modalexits = jQuery('#modalContainer').length;
      if (modalexits) {
        jQuery('#modalContainer').remove();
      }

      let modalContainer = document.createElement('div');
      modalContainer.id = 'modalContainer';
      document.body.appendChild(modalContainer);

      // Insert modal HTML into the dynamically created container
      modalContainer.innerHTML = data;
      jQuery('#ajax-loader').hide();
      // Open modal
      const modal = document.getElementById('customerInfoPopup');
      jQuery('#customerInfoPopup').modal('show');


      // Close modal event

    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to load modal content.');
    })
    .finally(() => {
      // Reset button state
      button.disabled = false;
      button.innerText = 'View';
    });
}
//load buttons
(function () {

jQuery(document).ready(function($) {
    $('.datepicker').datepicker({
        dateFormat: wpDateFormat.format // Use WordPress date format
    });
    console.log(wpDateFormat.format);
     $('.datepicker_multiple').datepicker({
        dateFormat: wpDateFormat.format,
         multipleDates: true 
    });
});

/**
 * Historical Photos Integration
 * Auto-save photos to patient history database
 * @since 9.6.0
 */
window.savePhotoToHistory = function(fileUrl, bodyLocation, markerX, markerY) {
    var appointmentId = jQuery('#appoinment_id').val();
    if (!appointmentId || !fileUrl) return;
    
    jQuery.ajax({
        url: ajax.url,
        type: 'POST',
        data: {
            action: 'auto_save_body_chart_photo',
            nonce: ajax.nonce,
            appointment_id: appointmentId,
            file_url: fileUrl,
            body_location: bodyLocation || 'unknown',
            marker_x: markerX || null,
            marker_y: markerY || null
        },
        success: function(response) {
            if (response.success) {
                console.log('Photo saved to history:', response.data);
            }
        },
        error: function() {
            console.log('Failed to save photo to history');
        }
    });
};
})();
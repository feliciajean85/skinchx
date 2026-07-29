<?php 

require_once(WPAMELIA_ADDON_PLUGIN_DIR . '/admin/encoderTableClass.php');
   // Import
    if (isset($_POST['comment_table_import']) && check_admin_referer('comment_table_csv_action', 'comment_table_csv_nonce')) {
        if (!empty($_FILES['comment_table_csv']['tmp_name'])) {
            $file = fopen($_FILES['comment_table_csv']['tmp_name'], 'r');
            $header = fgetcsv($file);

            while (($row = fgetcsv($file)) !== false) {
                $data = array_combine($header, $row);
                unset($data['id']); // Skip ID if present

                // Optional: format created_at
               // $data['created_at'] = current_time('mysql');

                $wpdb->insert($table_name, $data);
            }

            fclose($file);
            echo '<div class="updated notice"><p>CSV imported successfully.</p></div>';
        }
        else{
            echo '<div class="notice notice-error alert alert-danger danger"><p>CSV File Not Found.</p></div>';
        }
    }
$pagelimit=20;

$per_page = ($pagelimit) ? $pagelimit : 20;

$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

$paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']- 1) * $per_page)  : 0;

$orderby = (isset($_REQUEST['orderby']) && ($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'ID';

$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'DESC';
$table_name=$wpdb->prefix."amelia_report_comments";
$sql="SELECT  SQL_CALC_FOUND_ROWS *

FROM    $table_name  WHERE 1";


$query=$sql;



if($search){

	$query .= " AND (name='".$search."' OR LOWER(`name`) LIKE '%".strtolower($search)."%')";

}

 $query .= " GROUP   BY id ORDER BY `$orderby`  $order LIMIT $per_page OFFSET $paged";





$columns = array(

	         'cb'           => '<input type="checkbox" />', // Important for bulk actions
         
             'name' => 'Name',
           
            'fields' => 'Fields',
            'actions' => 'Actions'

);



$sortable_columns = array(

	'id' => array('id', true),

	'name' => array('name', true),

);

		

$table = new Eil_List_Table($table_name, $wpdb, 'amelia-list-comments', $per_page);

$table->set_columns($columns);

$table->set_sortable_columns($sortable_columns);

$table->set_query($query);



$table->prepare_items();

$bulk=$table->process_bulk_action();




$message = '';
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $wpdb->delete($table_name, array('id' => intval($_GET['id'])));

   
      $_SESSION['mesage']='<div class="updated"><p>Item Deleted</p></div>';
            
             wp_redirect(admin_url('admin.php?page='.$page_name.''));
             exit;
}

    global $wpdb;
   $table_name;

    // Export
    if (isset($_POST['comment_table_export']) && check_admin_referer('comment_table_csv_action', 'comment_table_csv_nonce')) {
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

        if (!empty($results)) {
            if (ob_get_length()) {
            ob_clean();
             }
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="comment_table_export.csv"');
            $output = fopen('php://output', 'w');

            // Header row
           
            fputcsv($output, array('name', 'description', 'fields')); // header row

            foreach ($results as $row) {
                 fputcsv($output, [
                $row['name'],
                $row['description'],
                $row['fields']
                ]);
            }

            fclose($output);
            exit;
        }
    }

 


?>

<div class="wrap kawaii-wrap pbwp bank-data-comments">

    <?php if (isset($_SESSION['success'])): ?>

    <div id="message" class="updated alert alert-success">

        <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>

    </div>

    <?php endif;?>

    <?php if (isset($_SESSION['error'])): ?>

    <div id="errormessage" class="updated alert alert-error">

        <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>

    </div>

    <?php endif;?>

    <div>

        <h1 class="pbwp-headingtag pbwp-mb-4 pbwp-p-1 kawaii-page-title">All Comments</h1>



    </div>

    <div>
  <form method="post" enctype="multipart/form-data" style="margin-bottom: 20px;">
        <?php wp_nonce_field('comment_table_csv_action', 'comment_table_csv_nonce'); ?>
        <input type="submit" name="comment_table_export" class="button button-primary" value="Export CSV" />
        <input type="file" name="comment_table_csv" />
        <input type="submit" name="comment_table_import" class="button" value="Import CSV" />
    </form>
        <?php $table->search_box('Search', 'search', 'Ex: Name or ID'); ?>
         <a class="page-title-action button button-primary" href="<?php echo admin_url('admin.php?page=amelia-list-comments&action=add')?>">Add New</a>
        
        <form method="get">

            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $table->display(); ?>

        </form>

    </div>



</div>

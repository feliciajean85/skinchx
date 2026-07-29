<?php

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );



class Eil_List_Table extends WP_List_Table {



	public $table_name;

	public $wpdb;

	public $page_class;

	public $post_per_page = 10;

	public $query;
 
	public $table_column = array();

	public $sortable_columns = array();
     public $total_item=0;
 

    function __construct($table_name, $wpdb, $page_class, $post_per_page=10,$totalitem=''){

        global $status, $page;

		$this->table_name = $table_name;

		$this->wpdb = $wpdb;

		$this->page_class = $page_class;

		$this->post_per_page = $post_per_page;
        $this->total_item=$totalitem;


        parent::__construct( array( 'singular' => 'list', 'plural' => 'lists', 'ajax' => false ) );

    }



    function column_default($item, $column_name){

       //print_r($column_name);

       
         switch ($column_name) {
       

            case 'actions':
              
            $edit_url = admin_url('admin.php?page=amelia-list-comments&action=edit&id=' . $item->id);
            $delete_url = admin_url('admin.php?page=amelia-list-comments&action=delete&id=' . $item->id);
            return '<a href="' . $edit_url . '">Edit</a> | <a href="' . $delete_url . '" style="color:red;">Delete</a>';
            
              default:

                return $item->$column_name;

               
        }

    }



	function column_id($item){
 
      
            $actions = array(

            'edit' => sprintf('<a href="?page='.$this->page_class.'&action=edit&id=%s">%s</a>', $item->id, __('Edit')),

            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s" class="rowdelete">%s</a>', $_REQUEST['page'], $item->id, __('Delete')),



        );  
             return sprintf('%s %s',

            '<a class="row-title" href="?page='.$this->page_class.'&opt=edit&id='.$item->id.'" title="'.$item->id.'">'.$item->id.'</a>',

            $this->row_actions($actions)

        );
      




    }



    function column_cb($item){

        return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->id );

    }



	public function set_columns($columns=array()){

		$this->table_column = $columns;

	}



	function get_columns(){

        return $this->table_column;

    }



	function set_sortable_columns($sortable_columns=array()){

		$this->sortable_columns = $sortable_columns;

	}



	function get_sortable_columns(){

        return $this->sortable_columns;

    }



    function get_bulk_actions() {

        $actions = array( 'delete' => 'Delete','export' => 'Export' );

        return $actions;

    }



    function process_bulk_action() {

        $deleteid=array();

       //global $wpdb;

		if( 'delete'===$this->current_action() ) {

			foreach($_GET['list'] as $id) {

               $deleteid[]=$id;

				$this->wpdb->query("DELETE FROM $this->table_name WHERE id=".$id);

        	}

            return $deleteid;

			//echo '<script>window.location="?page='.$this->page_class.'";</script>';

		  }
        if( 'export'===$this->current_action() ) {
            if (ob_get_length()) {
            ob_clean();
             }
           header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="comment_table_export.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, array('name', 'description', 'fields')); // header row
			foreach($_GET['list'] as $id) {

          
            //$exportid[]=$id;
            $results = $this->wpdb->get_results("SELECT * FROM $this->table_name where id=".$id."", ARRAY_A);

            foreach ($results as $row) {
                 fputcsv($output, [
                $row['name'],
                $row['description'],
                $row['fields']
                ]);
            }

           

        	}
             fclose($output);
             exit;

            //return $deleteid;

			//echo '<script>window.location="?page='.$this->page_class.'";</script>';

		  }
    }



	function set_query($query){

		$this->query = $query;

	}



	function get_query(){

		return $this->query;

	}



    function prepare_items() {

		$per_page = $this->post_per_page;



        $columns = $this->get_columns();

        $hidden = array();

        $sortable = $this->get_sortable_columns();



        $this->_column_headers = array($columns, $hidden, $sortable);



        $this->process_bulk_action();



		$query = $this->get_query();



		$data  = $this->wpdb->get_results($query);



		  $total_items =($this->total_item)?$this->total_item: $this->wpdb->get_var("SELECT FOUND_ROWS() as total");;

		$current_page = $this->get_pagenum();



        $this->items = $data;



        $this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $per_page, 'total_pages' => ceil($total_items/$per_page) ) );

    }



	function search_box( $text, $input_id, $placeholder='') {

		$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

		?>

       <div class="search-box" style="text-align:right; margin-bottom:10px;">

    <form class="search_form" name="search_form" method="POST" action="">

        <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>

        <input type="search" id="<?php echo $input_id ?>" name="search" value="<?php echo $search; ?>" placeholder="<?php echo $placeholder;?>" autocomplete="off" />

        <?php submit_button( $text, 'button', false, false, array('id' => 'search-submit') ); ?>

    </form>

   </div>

<?php

	}



}

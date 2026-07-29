<?php
$item = null;

if (isset($_POST['submit_item'])) {
    $name = sanitize_text_field($_POST['name']);
    $description = sanitize_textarea_field($_POST['description']);
    $fields = isset($_POST['fields']) ? implode(',', array_map('sanitize_text_field', $_POST['fields'])) : '';

    // Check if name already exists
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE name = %s AND id != %d",
            $name, intval($_GET['id'])
        ));
    } else {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE name = %s",
            $name
        ));
    }

    if ($existing > 0) {
        echo '<div class="notice notice-error"><p><strong>Error:</strong> Name already exists. Please use another name.</p></div>';
    } else {
        if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
            $wpdb->update($table_name, [
                'name' => $name,
                'description' => wp_unslash($description),
                'fields' => $fields,
            ], ['id' => intval($_GET['id'])]);
            echo '<div class="updated"><p>Item Updated</p></div>';
        } else {
            $wpdb->insert($table_name, [
                'name' => $name,
                'description' => wp_unslash($description),
                'fields' => $fields,
            ]);
            $_SESSION['mesage']='<div class="updated"><p>Comment Added</p></div>';
            
             wp_redirect(admin_url('admin.php?page='.$page_name.''));
             exit;
        }
    }
}
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_GET['id']), ARRAY_A);
    if ( isset($item['fields'])  && $item['fields']) {
        $item['fields'] = explode(',',$item['fields']);
    }
}
?>
<div class="wrap kawaii-wrap">
<h1 class="kawaii-page-title">Comment Manager</h1>
<div style="display:flex;justify-content:space-between; margin-bottom: 20px;">   
    <h2 style="margin:0; font-family: var(--kawaii-font); color: var(--kawaii-text);"><?php echo $item ? 'Edit Comment' : 'Add New Comment'; ?></h2> 
    <a class="button button-primary" href="<?php echo admin_url('admin.php?page='.$page_name.''); ?>">Back</a>
</div>
 
<div class="kawaii-card">
    <form method="post">
        <table class="form-table">
            <tr>
                <th><label>Name</label></th>
                <td><input type="text" name="name" value="<?php echo esc_attr($item['name'] ?? ''); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label>Description</label></th>
                <td><textarea name="description" class="large-text" style="min-height:200px;"><?php echo esc_textarea($item['description'] ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><label>Comment Fields Map</label></th>
                <td>
                    <label for="location"><input type="checkbox" id="location" name="fields[]" value="Referral:Locations" <?php if (!empty($item['fields']) && in_array('Referral:Locations', $item['fields'])) echo 'checked'; ?> > Referral:Locations</label><br>
                    <label for="his"><input id="his" type="checkbox" name="fields[]" value="Referral:History" <?php if (!empty($item['fields']) && in_array('Referral:History', $item['fields'])) echo 'checked'; ?>> Referral:History</label><br>
                    <label for="body"><input id="body" type="checkbox" name="fields[]" value="Referral:Features" <?php if (!empty($item['fields']) && in_array('Referral:Features', $item['fields'])) echo 'checked'; ?>> Referral:Features</label><br>
                    <label for="comment"><input id="comment" type="checkbox" name="fields[]" value="Referral:Comments" <?php if (!empty($item['fields']) && in_array('Referral:Comments', $item['fields'])) echo 'checked'; ?>>Referral:Comments</label> <br>
                    <label for="lession"><input id="lession" type="checkbox" name="fields[]" value="Info:Details" <?php if (!empty($item['fields']) && in_array('Info:Details', $item['fields'])) echo 'checked'; ?>>Info:Details</label><br>
                     <label for="pr"><input id="pr" type="checkbox" name="fields[]" value="Info:Notes" <?php if (!empty($item['fields']) && in_array('Info:Notes', $item['fields'])) echo 'checked'; ?>>Info:Notes</label><br>
                     <label for="pr"><input id="pr" type="checkbox" name="fields[]" value="Info:Comments" <?php if (!empty($item['fields']) && in_array('Info:Comments', $item['fields'])) echo 'checked'; ?>>Info:Comments</label>
                </td>
            </tr>
        </table>
        <p>
            <input type="submit" name="submit_item" class="button-primary" value="Save Item">
        </p>
    </form>
</div>
</div>
<?php
/**
 * Plugin Name: Member Only
 * Description: A plugin to handle member registration and redirect to the WooCommerce product page with automatic login.
 * Version: 1.6
 * Author: Hafiz Ullah
 * License: GPL2
 */

// Hook to initialize the plugin
function member_only_plugin_init() {
    add_role('member_only', 'Member Only', array(
        'read' => true,
        'level_0' => true,
    ));
}
add_action('init', 'member_only_plugin_init');

// Enqueue plugin's CSS file
function member_only_enqueue_styles() {
    wp_enqueue_style('member-only-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'member_only_enqueue_styles');


// Shortcode for the registration form
function member_only_form_shortcode() {
    ob_start();
    ?>
    <form id="member-only-form" action="" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('member_only_form', 'member_only_nonce'); ?>
        
        <label for="first_name">First Name (Required):</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="last_name">Last Name (Required):</label>
        <input type="text" id="last_name" name="last_name" required>

        <label for="email">Email Address (Required):</label>
        <input type="email" id="email" name="email" required>

        <label for="phone">Phone Number (Required):</label>
        <input type="text" id="phone" name="phone" required>

        <label for="password">Password (Required, min 6 chars):</label>
        <input type="password" id="password" name="password" required>

        <label for="company">Company Name (If Applicable):</label>
        <input type="text" id="company" name="company">

        <label for="attend_date">Event Attended Date (Required):</label>
        <input type="date" id="attend_date" name="attend_date" required>

        <label for="gov_id">Upload Government ID (JPG, PNG, PDF):</label>
        <input type="file" id="gov_id" name="gov_id" accept=".jpg,.png,.pdf" required>

        <input type="submit" name="submit_member" value="Register">
    </form>

    <?php
    if (isset($_POST['submit_member'])) {
        member_only_process_form();
    }
    return ob_get_clean();
}
add_shortcode('member_only_form', 'member_only_form_shortcode');


// Process form submission
function member_only_process_form() {
    if (!isset($_POST['member_only_nonce']) || !wp_verify_nonce($_POST['member_only_nonce'], 'member_only_form')) {
        echo 'Security check failed!';
        return;
    }

    if (isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['password'], $_POST['attend_date'])) {
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        // Check if user already exists
        if (email_exists($email)) {
            echo 'An account with this email already exists. Please login instead.';
            return;
        }

        // Validate password strength
        if (strlen($password) < 6) {
            echo 'Password must be at least 6 characters long.';
            return;
        }

        // Validate and upload government ID
        $gov_id = $_FILES['gov_id'];
        $upload_overrides = array('test_form' => false);

        $gov_upload = $gov_id['size'] > 0 ? wp_handle_upload($gov_id, $upload_overrides) : null;

        if ($gov_upload && isset($gov_upload['url'])) {
            // Create user with "member only" role
            $user_data = array(
                'user_login' => $email,
                'user_email' => $email,
                'user_pass' => $password,
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'role' => 'member_only', // Assigning "member only" role
            );

            $user_id = wp_insert_user($user_data);

            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'billing_first_name', sanitize_text_field($_POST['first_name']));
                update_user_meta($user_id, 'billing_last_name', sanitize_text_field($_POST['last_name']));
                update_user_meta($user_id, 'billing_email', $email);
                update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['phone']));
                update_user_meta($user_id, 'billing_company', sanitize_text_field($_POST['company']));
                update_user_meta($user_id, 'attend_date', sanitize_text_field($_POST['attend_date']));
                update_user_meta($user_id, 'gov_id', $gov_upload['url']);

                // Auto-login after registration
                $creds = array(
                    'user_login' => $email,
                    'user_password' => $password,
                    'remember' => true
                );

                $user = wp_signon($creds, false);

                if (is_wp_error($user)) {
                    echo 'Error during login. Please try again.';
                } else {
                    wp_redirect(home_url('/product/dodsc-membership-exclusive-access/'));
                    exit;
                }
            } else {
                echo 'Error during registration. Please try again.';
            }
        } else {
            echo 'Error uploading Government ID. Please try again.';
        }
    } else {
        echo 'Please fill in all required fields.';
    }
}



//login form 
function member_only_login_form_shortcode() {
    ob_start();
    ?>
    <form action="<?php echo wp_login_url(); ?>" method="post" class="member-login-form">
        <label for="user_login">Email or Username:</label>
        <input type="text" name="log" id="user_login" required>

        <label for="user_pass">Password:</label>
        <input type="password" name="pwd" id="user_pass" required>

        <input type="submit" value="Login">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('member_only_login_form', 'member_only_login_form_shortcode');



//Redirect after login
function member_only_redirect_after_login($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            return admin_url(); // Redirect admin to dashboard
        } elseif (in_array('member_only', $user->roles)) {
            return home_url('/profile/'); // Redirect "member only" users to the member-level page
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'member_only_redirect_after_login', 10, 3);


//Logout Redirection Funtion 
function member_only_custom_logout_url($logout_url, $redirect) {
    $user = wp_get_current_user(); // Get current user before they log out

    if (in_array('administrator', $user->roles)) {
        return add_query_arg('redirect_to', home_url('/'), $logout_url); // Admins → Home Page
    } else {
        return add_query_arg('redirect_to', home_url('/member-page/'), $logout_url); // Members → /member/
    }
}
add_filter('logout_url', 'member_only_custom_logout_url', 10, 2);


//member Profile 

function member_only_profile_page() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url() . '">log in</a> to view your profile.</p>';
    }

    $current_user = wp_get_current_user();
    $section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'profile';
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    ob_start();
    ?>

    <div class="member-dashboard">
        <!-- Left Sidebar Menu -->
        <div class="sidebar">
            <ul>
                <li><a href="?section=profile" class="<?php echo ($section == 'profile') ? 'active' : ''; ?>">Profile</a></li>
                <li><a href="?section=update_profile" class="<?php echo ($section == 'update_profile') ? 'active' : ''; ?>">Update Profile</a></li>
                <li><a href="?section=password_reset" class="<?php echo ($section == 'password_reset') ? 'active' : ''; ?>">Password Reset</a></li>
                <li><a href="?section=your_orders" class="<?php echo ($section == 'your_orders') ? 'active' : ''; ?>">Your Orders</a></li>
                
                <li><a href="<?php echo wp_logout_url(home_url('/member-page/')); ?>">Logout</a></li>
            </ul>
        </div>

        <!-- Right Side Content -->
        <div class="content">
            <?php
            if ($section == 'profile') {
                ?>
                <h2>Welcome To Your Profile</h2>
                <ul>
                    <li><strong>Name:</strong> <?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></li>
                    <li><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></li>
                    <li><strong>Username:</strong> <?php echo esc_html($current_user->user_login); ?></li>
                </ul>
                <?php
            } elseif ($section == 'update_profile') {
                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
                    check_admin_referer('update_profile_action', 'update_profile_nonce');

                    $first_name = sanitize_text_field($_POST['first_name']);
                    $last_name = sanitize_text_field($_POST['last_name']);
                    $email = sanitize_email($_POST['email']);

                    wp_update_user(array(
                        'ID'         => $current_user->ID,
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                        'user_email' => $email
                    ));
                    echo '<p class="success-msg">Profile updated successfully!</p>';
                }
                ?>

                <h2>Update Your Profile</h2>
                <form method="post">
                    <?php wp_nonce_field('update_profile_action', 'update_profile_nonce'); ?>
                    <label for="first_name">First Name:</label>
                    <input type="text" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" required>

                    <label for="last_name">Last Name:</label>
                    <input type="text" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" required>

                    <label for="email">Email:</label>
                    <input type="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>

                    <button type="submit" name="update_profile">Update Profile</button>
                </form>
                <?php
            } elseif ($section == 'password_reset') {
                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
                    check_admin_referer('password_reset_action', 'password_reset_nonce');

                    $new_password = sanitize_text_field($_POST['new_password']);
                    $confirm_password = sanitize_text_field($_POST['confirm_password']);

                    if ($new_password === $confirm_password && !empty($new_password)) {
                        wp_set_password($new_password, $current_user->ID);
                        echo '<p class="success-msg">Password updated successfully!</p>';
                    } else {
                        echo '<p class="error-msg">Passwords do not match or are empty.</p>';
                    }
                }
                ?>

                <h2>Reset Your Password</h2>
                <form method="post">
                    <?php wp_nonce_field('password_reset_action', 'password_reset_nonce'); ?>
                    <label for="new_password">New Password:</label>
                    <input type="password" name="new_password" required>

                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" name="confirm_password" required>

                    <button type="submit" name="reset_password">Reset Password</button>
                </form>
                <?php
            } elseif ($section == 'your_orders') {
                if (!class_exists('WooCommerce')) {
                    echo '<p>WooCommerce is not active.</p>';
                } else {
                    if ($order_id) {
                        // Show Order Details
                        $order = wc_get_order($order_id);
                        if ($order) {
                            echo '<h2>Order Details</h2>';
                            echo '<p><strong>Order ID:</strong> #' . $order->get_id() . '</p>';
                            echo '<p><strong>Date:</strong> ' . wc_format_datetime($order->get_date_created()) . '</p>';
                            echo '<p><strong>Status:</strong> ' . wc_get_order_status_name($order->get_status()) . '</p>';
                            echo '<p><strong>Total:</strong> ' . $order->get_formatted_order_total() . '</p>';
                            echo '<p><strong>Payment Method:</strong> ' . $order->get_payment_method_title() . '</p>';
                            echo '<h3>Items:</h3>';
                            echo '<ul>';
                            foreach ($order->get_items() as $item) {
                                echo '<li>' . $item->get_name() . ' x ' . $item->get_quantity() . '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>Invalid Order ID.</p>';
                        }
                    } else {
                        // Show Order List
                        $customer_orders = wc_get_orders(array(
                            'customer_id' => get_current_user_id(),
                            'status' => array('processing', 'completed', 'on-hold'),
                        ));

                        if ($customer_orders) {
                            echo '<h2>Your Orders</h2>';
                            echo '<table>';
                            echo '<tr><th>Order ID</th><th>Date</th><th>Status</th><th>Total Pay</th></tr>';

                            foreach ($customer_orders as $order) {
                                echo '<tr>';
                                echo '<td><a href="?section=your_orders&order_id=' . $order->get_id() . '">#' . $order->get_id() . '</a></td>';
                                echo '<td>' . wc_format_datetime($order->get_date_created()) . '</td>';
                                echo '<td>' . wc_get_order_status_name($order->get_status()) . '</td>';
                                echo '<td>' . $order->get_formatted_order_total() . '</td>';

                            }
                            echo '</table>';
                        } else {
                            echo '<p>No orders found.</p>';
                        }
                    }
                }
            }
            ?>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('member_profile', 'member_only_profile_page');




function custom_logout_button_shortcode() {
    if (is_user_logged_in()) {
        $logout_url = esc_url(wp_logout_url(home_url('/member/')));

        ob_start();
        ?>
        <form method="post" action="<?php echo $logout_url; ?>">
            <button type="submit" class="logout-button" onclick="return confirm('Are you sure you want to logout?');">
                Logout
            </button>
        </form>
        <?php
        return ob_get_clean();
    }
}
add_shortcode('logout_button', 'custom_logout_button_shortcode');

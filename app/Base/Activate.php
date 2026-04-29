<?php
/**
 * Activate class file.
 *
 * @package BillplzCF7
 */

namespace BillplzCF7\Base;

use WP_Query;

/**
 * Activate Class.
 */
class Activate {
    /**
	 * Method to activate the plugin.
	 *
	 * @return void
	 */
	public function activate() {
		\BillplzCF7\Model\PaymentDatabase::create_db();
		ob_start();
		$this->create_confirmation_page();
		$this->create_example_form();
		ob_end_clean();
	}

	/**
	 * Method to create a page.
	 *
	 * @param string $page_title The title of the page.
	 *
	 * @return int|null The ID of the created page or null if page already exists.
	 */
	private function create_page($page_title) {
		$args = array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			's'              => $page_title,
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			wp_reset_postdata();
			return null;
		} else {
			$redirect_page = $this->insert_page($page_title);
			$this->save_id($redirect_page);
			return $redirect_page;
		}
	}

    /**
	 * Method to create the confirmation page.
	 *
	 * @return int|null The ID of the created page or null if page already exists.
	 */
	private function create_confirmation_page() {
		$page_title = esc_html__( 'BCF7 Payment Confirmation', BCF7_TEXT_DOMAIN );
		return $this->create_page($page_title);
	}

    /**
	 * Method to create the BCF7 Example Form.
	 *
	 * @return int|null The ID of the created form or null if form already exists.
	 */
	private function create_example_form() {
		$form_title = 'BCF7 Example Payment Form';
		$form_content = 'Payment Form Example';

		$args = array(
			'post_type'      => 'wpcf7_contact_form',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			's'              => $form_title,
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			$form_id = (int) $query->posts[0]->ID;
			wp_reset_postdata();
			$this->save_form_id($form_id);
			return $form_id;
		} else {
			$form_id = $this->insert_form($form_title, $form_content);
			$this->add_form_meta($form_id);
			$this->save_form_id($form_id);
			return $form_id;
		}
	}

    /**
	 * Method to insert a page and return its ID.
	 *
	 * @param string $page_title The title of the page.
	 *
	 * @return int The ID of the inserted page.
	 */
	private function insert_page($page_title) {
		$post_id = wp_insert_post(
			array(
				'post_title'     => $page_title,
				'post_name'      => sanitize_title($page_title),
				'post_content'   => '<!-- wp:shortcode -->[bcf7_payment_confirmation]<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'post_author'    => get_current_user_id(),
				'post_type'      => 'page',
				'comment_status' => 'closed',
			));

        return $post_id;
    }

    /**
     * Method to save the page ID in the options table.
     *
     * @param int $redirect_page_id The ID of the page.
     *
     * @return void
     */
    private function save_id($redirect_page_id) {
        $options = get_option('bcf7_general_settings');

        if ($options) {
            if (isset($options['bcf7_redirect_page']) && $options['bcf7_redirect_page'] === $redirect_page_id) {
                return;
            } else {
                $options['bcf7_redirect_page'] = $redirect_page_id;
                update_option('bcf7_general_settings', $options);
            }
        } else {
            $options = array(
                'bcf7_mode'            => '1',
                'bcf7_form_select'     => '',
                'bcf7_redirect_page'   => $redirect_page_id,
            );
            add_option('bcf7_general_settings', $options);
        }
    }

    /**
     * Method to save the example payment form ID in the options table.
     *
     * @param int $form_id The ID of the form.
     *
     * @return void
     */
    private function save_form_id($form_id) {
        if (empty($form_id)) {
            return;
        }

        $options = get_option('bcf7_general_settings');

        if (! is_array($options)) {
            $options = array(
                'bcf7_mode'          => '1',
                'bcf7_form_select'   => array($form_id),
                'bcf7_redirect_page' => '',
            );

            add_option('bcf7_general_settings', $options);
            return;
        }

        $selected_forms = isset($options['bcf7_form_select']) ? array_filter(array_map('intval', (array) $options['bcf7_form_select'])) : array();

        if (! empty($selected_forms)) {
            return;
        }

        $options['bcf7_form_select'] = array($form_id);
        update_option('bcf7_general_settings', $options);
    }

    /**
     * Method to insert a form and return its ID.
     *
     * @param string $form_title    The title of the form.
     * @param string $form_content  The content of the form.
     *
     * @return int The ID of the inserted form.
     */
    private function insert_form($form_title, $form_content) {
        $post_data = array(
            'post_title'     => $form_title,
            'post_content'   => $form_content,
            'post_status'    => 'publish',
            'post_author'    => get_current_user_id(),
            'post_type'      => 'wpcf7_contact_form',
            'comment_status' => 'closed',
        );

        $post_id = wp_insert_post($post_data);

        return $post_id;
    }

    /**
     * Method to add meta data for the form.
     *
     * @param int $form_id The ID of the form.
     *
     * @return void
     */
    private function add_form_meta($form_id) {
        $form = '
        <label> Name
            [text* bcf7-name] </label>

        <label> Your email
            [email* bcf7-email] </label>

        <label> Phone
            [tel* bcf7-phone] </label>

        <label> Amount (RM)
            [number* bcf7-amount] </label>


        [submit "Submit"]';

        add_post_meta($form_id, '_additional_settings', 'skip_mail: on');
        add_post_meta($form_id, '_form', $form);
    }
}

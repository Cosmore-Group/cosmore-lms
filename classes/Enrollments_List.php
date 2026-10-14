<?php
/**
 * Enrollments admin page.
 *
 * The Enrollments entry of the Tutor menu is empty in the free plugin (the real screen is a
 * Tutor Pro addon). This page does the same job with the plugin's own functions: list the
 * enrollments, enroll a user in a course by hand, and approve, cancel or delete one.
 *
 * Enrollments are `tutor_enrolled` posts: the student is the author, the course the parent.
 *
 * @package Tutor
 * @since 4.1.1
 */

namespace TUTOR;

defined( 'ABSPATH' ) || exit;

use Tutor\Models\EnrollmentModel;

/**
 * Class Enrollments_List
 */
class Enrollments_List {

	const PAGE_SLUG = 'tutor_enrollments';

	const ACTION_CREATE = 'tutor_enrollment_create';
	const ACTION_STATUS = 'tutor_enrollment_status';
	const ACTION_DELETE = 'tutor_enrollment_delete';

	/**
	 * Page title, read by the view.
	 *
	 * @var string
	 */
	public $page_title;

	/**
	 * Register the form handlers.
	 */
	public function __construct() {
		$this->page_title = __( 'Enrollments', 'tutor' );

		add_action( 'admin_post_' . self::ACTION_CREATE, array( $this, 'create' ) );
		add_action( 'admin_post_' . self::ACTION_STATUS, array( $this, 'change_status' ) );
		add_action( 'admin_post_' . self::ACTION_DELETE, array( $this, 'delete' ) );
	}

	/**
	 * Enrollment statuses and their labels.
	 *
	 * @return array<string, string>
	 */
	public static function statuses() {
		return array(
			EnrollmentModel::STATUS_COMPLETED => __( 'Approved', 'tutor' ),
			EnrollmentModel::STATUS_PENDING   => __( 'Pending', 'tutor' ),
			EnrollmentModel::STATUS_CANCEL    => __( 'Cancelled', 'tutor' ),
		);
	}

	/**
	 * Badge class for a status.
	 *
	 * @param string $status status.
	 *
	 * @return string
	 */
	public static function badge( $status ) {
		$map = array(
			EnrollmentModel::STATUS_COMPLETED => 'success',
			EnrollmentModel::STATUS_PENDING   => 'warning',
			EnrollmentModel::STATUS_CANCEL    => 'danger',
		);

		return $map[ $status ] ?? 'default';
	}

	/**
	 * Page URL.
	 *
	 * @param array $args query args.
	 *
	 * @return string
	 */
	public static function url( $args = array() ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_tutor' ) ) {
			wp_die( esc_html__( 'You do not have permission to see this page.', 'tutor' ) );
		}

		include tutor()->path . 'views/pages/enrollments.php';
	}

	/**
	 * Check the capability and the nonce of an action.
	 *
	 * @param string $nonce_action nonce action.
	 *
	 * @return void
	 */
	private function authorize( $nonce_action ) {
		if ( ! current_user_can( 'manage_tutor' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'tutor' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * Go back to the list with a result code.
	 *
	 * @param string $done result code.
	 *
	 * @return void
	 */
	private function back( $done ) {
		wp_safe_redirect( self::url( array( 'done' => $done ) ) );
		exit;
	}

	/**
	 * Enroll a user in a course.
	 *
	 * @return void
	 */
	public function create() {
		$this->authorize( self::ACTION_CREATE );

		$user_id   = Input::post( 'user_id', 0, Input::TYPE_INT );
		$course_id = Input::post( 'course_id', 0, Input::TYPE_INT );

		if ( ! $user_id || ! $course_id || ! get_userdata( $user_id ) || get_post_type( $course_id ) !== tutor()->course_post_type ) {
			$this->back( 'invalid' );
		}

		$this->back( EnrollmentModel::do_enroll( $course_id, 0, $user_id ) ? 'enrolled' : 'refused' );
	}

	/**
	 * Approve or cancel an enrollment.
	 *
	 * @return void
	 */
	public function change_status() {
		$id = Input::get( 'enrollment', 0, Input::TYPE_INT );
		$this->authorize( self::ACTION_STATUS . $id );

		$to = sanitize_key( Input::get( 'to', '' ) );

		if ( get_post_type( $id ) === EnrollmentModel::POST_TYPE && array_key_exists( $to, self::statuses() ) ) {
			EnrollmentModel::update_enrollments( $to, array( $id ) );
			clean_post_cache( $id );
		}

		$this->back( 'updated' );
	}

	/**
	 * Delete an enrollment.
	 *
	 * @return void
	 */
	public function delete() {
		$id = Input::get( 'enrollment', 0, Input::TYPE_INT );
		$this->authorize( self::ACTION_DELETE . $id );

		if ( get_post_type( $id ) === EnrollmentModel::POST_TYPE ) {
			wp_delete_post( $id, true );
		}

		$this->back( 'deleted' );
	}
}

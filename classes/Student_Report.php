<?php
/**
 * Student detail page in wp-admin.
 *
 * Replaces the page of the Tutor Pro Report addon that the Students list linked to
 * (`admin.php?page=tutor_report&sub_page=students&student_id=…`): the page is registered
 * under the same slug, hidden from the menu, so the existing link keeps working.
 *
 * @package Tutor
 * @since 4.1.1
 */

namespace TUTOR;

defined( 'ABSPATH' ) || exit;

use Tutor\Models\CourseModel;
use Tutor\Models\QuizModel;

/**
 * Class Student_Report
 */
class Student_Report {

	const PAGE_SLUG = 'tutor_report';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
	}

	/**
	 * Register the page and hide it from the menu: it is reached from the Students list.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'tutor',
			__( 'Student', 'tutor' ),
			__( 'Student', 'tutor' ),
			'manage_tutor',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);

		remove_submenu_page( 'tutor', self::PAGE_SLUG );
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render() {
		$student_id = Input::get( 'student_id', 0, Input::TYPE_INT );
		$student    = $student_id ? get_userdata( $student_id ) : false;

		echo '<div class="wrap">';

		if ( ! $student ) {
			echo '<h1>' . esc_html__( 'Student', 'tutor' ) . '</h1>';
			echo '<p>' . esc_html__( 'Student not found.', 'tutor' ) . '</p></div>';
			return;
		}

		$back = admin_url( 'admin.php?page=' . Students_List::STUDENTS_LIST_PAGE );

		echo '<h1 class="wp-heading-inline">' . esc_html( $student->display_name ) . '</h1>';
		echo ' <a href="' . esc_url( $back ) . '" class="page-title-action">' . esc_html__( 'All students', 'tutor' ) . '</a>';
		echo '<hr class="wp-header-end">';

		$this->render_summary( $student );
		$this->render_courses( $student );
		$this->render_quizzes( $student );

		echo '</div>';
	}

	/**
	 * Basic facts about the student.
	 *
	 * @param \WP_User $student student.
	 *
	 * @return void
	 */
	protected function render_summary( $student ) {
		$enrolled  = tutor_utils()->get_enrolled_courses_ids_by_user( $student->ID );
		$completed = tutor_utils()->get_completed_courses_ids_by_user( $student->ID );

		$rows = array(
			__( 'Email', 'tutor' )              => $student->user_email,
			__( 'Registered', 'tutor' )         => tutor_i18n_get_formated_date( $student->user_registered, get_option( 'date_format' ) ),
			__( 'Enrolled courses', 'tutor' )   => is_array( $enrolled ) ? count( $enrolled ) : 0,
			__( 'Completed courses', 'tutor' )  => is_array( $completed ) ? count( $completed ) : 0,
		);

		echo '<table class="widefat striped" style="max-width:640px;margin-top:16px"><tbody>';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th scope="row" style="width:220px">' . esc_html( $label ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Enrolled courses with progress.
	 *
	 * @param \WP_User $student student.
	 *
	 * @return void
	 */
	protected function render_courses( $student ) {
		$courses = CourseModel::get_enrolled_courses_by_user( $student->ID, array( 'private', 'publish' ) );

		echo '<h2 style="margin-top:32px">' . esc_html__( 'Courses', 'tutor' ) . '</h2>';

		if ( ! $courses || ! $courses->have_posts() ) {
			echo '<p>' . esc_html__( 'This student is not enrolled in any course.', 'tutor' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Course', 'tutor' ) . '</th>';
		echo '<th>' . esc_html__( 'Progress', 'tutor' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'tutor' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $courses->posts as $course ) {
			$progress  = tutor_utils()->get_course_completed_percent( $course->ID, $student->ID, true );
			$percent   = (int) ( $progress['completed_percent'] ?? 0 );
			$completed = tutor_utils()->is_completed_course( $course->ID, $student->ID );

			echo '<tr>';
			echo '<td><a href="' . esc_url( get_edit_post_link( $course->ID ) ?: get_permalink( $course->ID ) ) . '">' . esc_html( get_the_title( $course ) ) . '</a></td>';
			echo '<td>' . esc_html(
				sprintf(
					/* translators: 1: completed lessons 2: total lessons 3: percent */
					__( '%1$d of %2$d · %3$d%%', 'tutor' ),
					(int) ( $progress['completed_count'] ?? 0 ),
					(int) ( $progress['total_count'] ?? 0 ),
					$percent
				)
			) . '</td>';
			echo '<td>' . esc_html( $completed ? __( 'Completed', 'tutor' ) : __( 'In progress', 'tutor' ) ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Latest quiz attempts.
	 *
	 * @param \WP_User $student student.
	 *
	 * @return void
	 */
	protected function render_quizzes( $student ) {
		$attempted = QuizModel::get_attempted_quizzes( $student->ID, array(), 0, 20, 'DESC' );
		$rows      = ( new QuizModel() )->get_formatted_quiz_attempt_list_by_quiz_id( $attempted['results'] ?? array() );

		echo '<h2 style="margin-top:32px">' . esc_html__( 'Quiz attempts', 'tutor' ) . '</h2>';

		if ( empty( $rows ) ) {
			echo '<p>' . esc_html__( 'No quiz attempts yet.', 'tutor' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Quiz', 'tutor' ) . '</th>';
		echo '<th>' . esc_html__( 'Course', 'tutor' ) . '</th>';
		echo '<th>' . esc_html__( 'Date', 'tutor' ) . '</th>';
		echo '<th>' . esc_html__( 'Marks', 'tutor' ) . '</th>';
		echo '<th>' . esc_html__( 'Result', 'tutor' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			foreach ( (array) ( $row['attempts'] ?? array() ) as $attempt ) {
				echo '<tr>';
				echo '<td>' . esc_html( $row['quiz_title'] ?? '' ) . '</td>';
				echo '<td>' . esc_html( $row['course_title'] ?? '' ) . '</td>';
				echo '<td>' . esc_html( $attempt['date'] ?? '' ) . '</td>';
				echo '<td>' . esc_html( (string) round( (float) ( $attempt['marks_percent'] ?? 0 ) ) ) . '%</td>';
				echo '<td>' . esc_html( ucfirst( (string) ( $attempt['result'] ?? '' ) ) ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody></table>';
	}
}

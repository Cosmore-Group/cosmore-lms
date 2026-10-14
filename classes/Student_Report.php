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
	 * Register the page without a parent, so it has no menu entry: it is reached from the
	 * Students list. (Registering it under `tutor` and removing it from the menu leaves
	 * WordPress unable to resolve the parent, and the page answers "not allowed".)
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'',
			__( 'Student', 'tutor' ),
			__( 'Student', 'tutor' ),
			'manage_tutor',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render() {
		$student_id = Input::get( 'student_id', 0, Input::TYPE_INT );
		$student    = $student_id ? get_userdata( $student_id ) : false;

		$data = array(
			'student'  => $student,
			'back_url' => admin_url( 'admin.php?page=' . Students_List::STUDENTS_LIST_PAGE ),
			'summary'  => array(),
			'courses'  => array(),
			'quizzes'  => array(),
		);

		if ( $student ) {
			$data['summary'] = $this->summary( $student );
			$data['courses'] = $this->courses( $student );
			$data['quizzes'] = $this->quizzes( $student );
		}

		tutor_load_template_from_custom_path( tutor()->path . 'views/pages/student-details.php', $data );
	}

	/**
	 * Basic facts about the student.
	 *
	 * @param \WP_User $student student.
	 *
	 * @return array<string, string|int>
	 */
	protected function summary( $student ) {
		$enrolled  = tutor_utils()->get_enrolled_courses_ids_by_user( $student->ID );
		$completed = tutor_utils()->get_completed_courses_ids_by_user( $student->ID );

		return array(
			__( 'Email', 'tutor' )             => $student->user_email,
			__( 'Registration Date', 'tutor' ) => tutor_i18n_get_formated_date( $student->user_registered, get_option( 'date_format' ) ),
			__( 'Enrolled courses', 'tutor' )  => is_array( $enrolled ) ? count( $enrolled ) : 0,
			__( 'Completed courses', 'tutor' ) => is_array( $completed ) ? count( $completed ) : 0,
		);
	}

	/**
	 * Enrolled courses with progress.
	 *
	 * @param \WP_User $student student.
	 *
	 * @return array<int, array{title: string, url: string, done: int, total: int, percent: int, completed: bool}>
	 */
	protected function courses( $student ) {
		$query = CourseModel::get_enrolled_courses_by_user( $student->ID, array( 'private', 'publish' ) );
		$rows  = array();

		if ( ! $query || ! $query->have_posts() ) {
			return $rows;
		}

		foreach ( $query->posts as $course ) {
			$progress = tutor_utils()->get_course_completed_percent( $course->ID, $student->ID, true );

			$rows[] = array(
				'title'     => get_the_title( $course ),
				'url'       => get_edit_post_link( $course->ID, 'raw' ) ? get_edit_post_link( $course->ID, 'raw' ) : get_permalink( $course->ID ),
				'done'      => (int) ( $progress['completed_count'] ?? 0 ),
				'total'     => (int) ( $progress['total_count'] ?? 0 ),
				'percent'   => (int) ( $progress['completed_percent'] ?? 0 ),
				'completed' => (bool) tutor_utils()->is_completed_course( $course->ID, $student->ID ),
			);
		}

		return $rows;
	}

	/**
	 * Latest quiz attempts.
	 *
	 * @param \WP_User $student student.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function quizzes( $student ) {
		$attempted = QuizModel::get_attempted_quizzes( $student->ID, array(), 0, 20, 'DESC' );
		$list      = ( new QuizModel() )->get_formatted_quiz_attempt_list_by_quiz_id( $attempted['results'] ?? array() );
		$rows      = array();

		foreach ( $list as $quiz ) {
			foreach ( (array) ( $quiz['attempts'] ?? array() ) as $attempt ) {
				$rows[] = array(
					'quiz'    => (string) ( $quiz['quiz_title'] ?? '' ),
					'course'  => (string) ( $quiz['course_title'] ?? '' ),
					'date'    => (string) ( $attempt['date'] ?? '' ),
					'percent' => (string) round( (float) ( $attempt['marks_percent'] ?? 0 ) ),
					'result'  => (string) ( $attempt['result'] ?? '' ),
				);
			}
		}

		return $rows;
	}
}

<?php
/**
 * Student details template.
 *
 * @package Tutor\Views
 * @subpackage Tutor\Students
 * @since 4.1.1
 *
 * @var array $data student, back_url, summary, courses, quizzes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// `tutor_load_template_from_custom_path()` hands the data over as `$data`.
$student  = $data['student'] ?? false;
$back_url = $data['back_url'] ?? '';
$summary  = $data['summary'] ?? array();
$courses  = $data['courses'] ?? array();
$quizzes  = $data['quizzes'] ?? array();

$navbar_data = array(
	'page_title' => $student ? $student->display_name : __( 'Student', 'tutor' ),
);
?>

<div class="tutor-admin-wrap">
	<?php tutor_load_template_from_custom_path( tutor()->path . 'views/elements/list-navbar.php', $navbar_data ); ?>

	<div class="tutor-admin-container tutor-admin-container-lg tutor-mt-16">
		<a href="<?php echo esc_url( $back_url ); ?>" class="tutor-btn tutor-btn-tertiary tutor-btn-sm tutor-mb-16">
			<?php esc_html_e( 'All students', 'tutor' ); ?>
		</a>

		<?php if ( ! $student ) : ?>
			<?php tutils()->render_list_empty_state( array( 'title' => __( 'Student not found.', 'tutor' ) ) ); ?>
		<?php else : ?>
			<div class="tutor-card tutor-p-24 tutor-mb-24">
				<div class="tutor-d-flex tutor-align-center tutor-gap-2 tutor-mb-16">
					<?php echo wp_kses( tutor_utils()->get_tutor_avatar( $student->ID ), tutor_utils()->allowed_avatar_tags() ); ?>
					<div class="tutor-fs-5 tutor-fw-bold"><?php echo esc_html( $student->display_name ); ?></div>
				</div>

				<div class="tutor-row">
					<?php foreach ( $summary as $label => $value ) : ?>
						<div class="tutor-col-6 tutor-col-lg-3 tutor-mb-8">
							<div class="tutor-fs-7 tutor-color-muted"><?php echo esc_html( $label ); ?></div>
							<div class="tutor-fs-6 tutor-fw-medium"><?php echo esc_html( (string) $value ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<h3 class="tutor-fs-6 tutor-fw-bold tutor-mb-12"><?php esc_html_e( 'Courses', 'tutor' ); ?></h3>
			<?php if ( $courses ) : ?>
				<div class="tutor-table-responsive tutor-dashboard-list-table tutor-mb-32">
					<table class="tutor-table tutor-table-middle">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Course', 'tutor' ); ?></th>
								<th><?php esc_html_e( 'Progress', 'tutor' ); ?></th>
								<th><?php esc_html_e( 'Status', 'tutor' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $courses as $course ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( $course['url'] ); ?>" class="tutor-fs-7 tutor-fw-medium"><?php echo esc_html( $course['title'] ); ?></a></td>
									<td>
										<span class="tutor-fs-7">
											<?php
											/* translators: 1: completed lessons 2: total lessons 3: percent */
											echo esc_html( sprintf( __( '%1$d of %2$d · %3$d%%', 'tutor' ), $course['done'], $course['total'], $course['percent'] ) );
											?>
										</span>
									</td>
									<td>
										<span class="tutor-badge-label label-<?php echo $course['completed'] ? 'success' : 'warning'; ?>">
											<?php echo esc_html( $course['completed'] ? __( 'Completed', 'tutor' ) : __( 'In Progress', 'tutor' ) ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<?php tutils()->render_list_empty_state( array( 'title' => __( 'This student is not enrolled in any course.', 'tutor' ) ) ); ?>
			<?php endif; ?>

			<h3 class="tutor-fs-6 tutor-fw-bold tutor-mb-12"><?php esc_html_e( 'Quiz Attempts', 'tutor' ); ?></h3>
			<?php if ( $quizzes ) : ?>
				<div class="tutor-table-responsive tutor-dashboard-list-table">
					<table class="tutor-table tutor-table-middle">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Quiz', 'tutor' ); ?></th>
								<th><?php esc_html_e( 'Course', 'tutor' ); ?></th>
								<th><?php esc_html_e( 'Date', 'tutor' ); ?></th>
								<th><?php esc_html_e( 'Marks', 'tutor' ); ?></th>
								<th><?php esc_html_e( 'Result', 'tutor' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $quizzes as $attempt ) : ?>
								<tr>
									<td><span class="tutor-fs-7 tutor-fw-medium"><?php echo esc_html( $attempt['quiz'] ); ?></span></td>
									<td><span class="tutor-fs-7"><?php echo esc_html( $attempt['course'] ); ?></span></td>
									<td><span class="tutor-fs-7"><?php echo esc_html( $attempt['date'] ); ?></span></td>
									<td><span class="tutor-fs-7"><?php echo esc_html( $attempt['percent'] ); ?>%</span></td>
									<td>
										<span class="tutor-badge-label label-<?php echo 'pass' === $attempt['result'] ? 'success' : ( 'fail' === $attempt['result'] ? 'danger' : 'warning' ); ?>">
											<?php echo esc_html( ucfirst( $attempt['result'] ) ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<?php tutils()->render_list_empty_state( array( 'title' => __( 'No quiz attempts yet.', 'tutor' ) ) ); ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

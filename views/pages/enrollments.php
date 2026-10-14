<?php
/**
 * Enrollments list template.
 *
 * @package Tutor\Views
 * @subpackage Tutor\Enrollments
 * @since 4.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TUTOR\Enrollments_List;
use TUTOR\Input;
use Tutor\Models\EnrollmentModel;

$statuses = Enrollments_List::statuses();
$status   = sanitize_key( Input::get( 'status', '' ) );
$status   = array_key_exists( $status, $statuses ) ? $status : '';
$paged    = max( 1, Input::get( 'paged', 1, Input::TYPE_INT ) );
$per_page = (int) tutor_utils()->get_option( 'pagination_per_page', 20 );

$query = new WP_Query(
	array(
		'post_type'      => EnrollmentModel::POST_TYPE,
		'post_status'    => $status ? $status : array_keys( $statuses ),
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

$counts = wp_count_posts( EnrollmentModel::POST_TYPE );
$total  = 0;
foreach ( array_keys( $statuses ) as $key ) {
	$total += (int) ( $counts->$key ?? 0 );
}

$users   = get_users(
	array(
		'orderby' => 'display_name',
		'number'  => 500,
		'fields'  => array( 'ID', 'display_name', 'user_email' ),
	)
);
$courses = get_posts(
	array(
		'post_type'      => tutor()->course_post_type,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);

$notices = array(
	'enrolled' => array( 'success', __( 'The student is now enrolled.', 'tutor' ) ),
	'refused'  => array( 'danger', __( 'The enrollment was refused: check that the course is published.', 'tutor' ) ),
	'invalid'  => array( 'danger', __( 'Choose both a user and a course.', 'tutor' ) ),
	'updated'  => array( 'success', __( 'Enrollment updated.', 'tutor' ) ),
	'deleted'  => array( 'success', __( 'Enrollment deleted.', 'tutor' ) ),
);
$done    = sanitize_key( Input::get( 'done', '' ) );

$navbar_data = array(
	'page_title' => __( 'Enrollments', 'tutor' ),
);
?>

<div class="tutor-admin-wrap">
	<?php tutor_load_template_from_custom_path( tutor()->path . 'views/elements/list-navbar.php', $navbar_data ); ?>

	<div class="tutor-admin-container tutor-admin-container-lg tutor-mt-16">
		<?php if ( isset( $notices[ $done ] ) ) : ?>
			<div class="tutor-alert tutor-<?php echo esc_attr( $notices[ $done ][0] ); ?> tutor-mb-16">
				<div class="tutor-alert-text"><?php echo esc_html( $notices[ $done ][1] ); ?></div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $courses ) ) : ?>
			<div class="tutor-card tutor-p-24 tutor-mb-24">
				<h3 class="tutor-fs-6 tutor-fw-bold tutor-mb-16"><?php esc_html_e( 'Enroll a student', 'tutor' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tutor-d-flex tutor-flex-wrap tutor-align-end tutor-gap-2">
					<input type="hidden" name="action" value="<?php echo esc_attr( Enrollments_List::ACTION_CREATE ); ?>">
					<?php wp_nonce_field( Enrollments_List::ACTION_CREATE ); ?>

					<div>
						<label class="tutor-form-label" for="enroll-user"><?php esc_html_e( 'Student', 'tutor' ); ?></label>
						<select name="user_id" id="enroll-user" class="tutor-form-control" required>
							<option value=""><?php esc_html_e( 'Choose a user…', 'tutor' ); ?></option>
							<?php foreach ( $users as $user ) : ?>
								<option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div>
						<label class="tutor-form-label" for="enroll-course"><?php esc_html_e( 'Course', 'tutor' ); ?></label>
						<select name="course_id" id="enroll-course" class="tutor-form-control" required>
							<option value=""><?php esc_html_e( 'Choose a course…', 'tutor' ); ?></option>
							<?php foreach ( $courses as $course ) : ?>
								<option value="<?php echo esc_attr( $course->ID ); ?>"><?php echo esc_html( get_the_title( $course ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<button type="submit" class="tutor-btn tutor-btn-primary"><?php esc_html_e( 'Enroll', 'tutor' ); ?></button>
				</form>
			</div>
		<?php endif; ?>

		<div class="tutor-d-flex tutor-gap-1 tutor-mb-16">
			<a href="<?php echo esc_url( Enrollments_List::url() ); ?>" class="tutor-btn tutor-btn-sm <?php echo '' === $status ? 'tutor-btn-primary' : 'tutor-btn-outline-primary'; ?>">
				<?php echo esc_html( sprintf( '%s (%d)', __( 'All', 'tutor' ), $total ) ); ?>
			</a>
			<?php foreach ( $statuses as $key => $label ) : ?>
				<a href="<?php echo esc_url( Enrollments_List::url( array( 'status' => $key ) ) ); ?>" class="tutor-btn tutor-btn-sm <?php echo $status === $key ? 'tutor-btn-primary' : 'tutor-btn-outline-primary'; ?>">
					<?php echo esc_html( sprintf( '%s (%d)', $label, (int) ( $counts->$key ?? 0 ) ) ); ?>
				</a>
			<?php endforeach; ?>
		</div>

		<?php if ( $query->have_posts() ) : ?>
			<div class="tutor-table-responsive tutor-dashboard-list-table">
				<table class="tutor-table tutor-table-middle">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Student', 'tutor' ); ?></th>
							<th><?php esc_html_e( 'Course', 'tutor' ); ?></th>
							<th><?php esc_html_e( 'Enrolled on', 'tutor' ); ?></th>
							<th><?php esc_html_e( 'Status', 'tutor' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $query->posts as $enrollment ) :
							$student   = get_userdata( (int) $enrollment->post_author );
							$course_id = (int) $enrollment->post_parent;
							$state     = $enrollment->post_status;
							?>
							<tr>
								<td>
									<?php if ( $student ) : ?>
										<div class="tutor-d-flex tutor-align-center tutor-gap-1">
											<?php echo wp_kses( tutor_utils()->get_tutor_avatar( $student->ID ), tutor_utils()->allowed_avatar_tags() ); ?>
											<div>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor_report&sub_page=students&student_id=' . $student->ID ) ); ?>" class="tutor-fs-7 tutor-fw-medium">
													<?php echo esc_html( $student->display_name ); ?>
												</a>
												<div class="tutor-fs-8 tutor-color-muted"><?php echo esc_html( $student->user_email ); ?></div>
											</div>
										</div>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td><?php echo $course_id ? esc_html( get_the_title( $course_id ) ) : '—'; ?></td>
								<td><span class="tutor-fs-7"><?php echo esc_html( tutor_i18n_get_formated_date( $enrollment->post_date_gmt, get_option( 'date_format' ) ) ); ?></span></td>
								<td>
									<span class="tutor-badge-label label-<?php echo esc_attr( Enrollments_List::badge( $state ) ); ?>">
										<?php echo esc_html( $statuses[ $state ] ?? $state ); ?>
									</span>
								</td>
								<td>
									<div class="tutor-d-flex tutor-align-center tutor-gap-1 tutor-justify-end">
										<?php
										$link = static function ( $action, $args ) use ( $enrollment ) {
											return wp_nonce_url(
												add_query_arg( array_merge( array( 'action' => $action, 'enrollment' => $enrollment->ID ), $args ), admin_url( 'admin-post.php' ) ),
												$action . $enrollment->ID
											);
										};
										?>
										<?php if ( EnrollmentModel::STATUS_COMPLETED !== $state ) : ?>
											<a href="<?php echo esc_url( $link( Enrollments_List::ACTION_STATUS, array( 'to' => EnrollmentModel::STATUS_COMPLETED ) ) ); ?>" class="tutor-btn tutor-btn-tertiary tutor-btn-sm"><?php esc_html_e( 'Approve', 'tutor' ); ?></a>
										<?php endif; ?>
										<?php if ( EnrollmentModel::STATUS_CANCEL !== $state ) : ?>
											<a href="<?php echo esc_url( $link( Enrollments_List::ACTION_STATUS, array( 'to' => EnrollmentModel::STATUS_CANCEL ) ) ); ?>" class="tutor-btn tutor-btn-tertiary tutor-btn-sm"><?php esc_html_e( 'Cancel', 'tutor' ); ?></a>
										<?php endif; ?>
										<a href="<?php echo esc_url( $link( Enrollments_List::ACTION_DELETE, array() ) ); ?>" class="tutor-btn tutor-btn-tertiary tutor-btn-sm" onclick="return confirm('<?php echo esc_js( __( 'Delete this enrollment?', 'tutor' ) ); ?>');"><?php esc_html_e( 'Delete', 'tutor' ); ?></a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<?php tutils()->render_list_empty_state(); ?>
		<?php endif; ?>

		<div class="tutor-admin-page-pagination-wrapper tutor-mt-32">
			<?php
			if ( (int) $query->found_posts > $per_page ) {
				tutor_load_template_from_custom_path(
					tutor()->path . 'views/elements/pagination.php',
					array(
						'total_items' => (int) $query->found_posts,
						'per_page'    => $per_page,
						'paged'       => $paged,
					)
				);
			}
			?>
		</div>
	</div>
</div>

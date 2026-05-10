<?php
/**
 * FILE: member.php
 * PURPOSE: Shows the Reading Clubs page where readers can create/join/leave clubs, share current reads, and post discussions.
 * USED BY: `public/member.php` endpoint (via `ReadingClubController`) after it prepares club lists and discussion data.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/member.php reading clubs UI ?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Reading Clubs | IBRCN</title>
	<link rel="stylesheet" href="./css/style.css">
	<style>
		.club-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap:1.6rem; }
		.club-card{ background:#fff;border:1px solid rgba(0,0,0,0.06);padding:1.2rem;border-radius:1rem; box-shadow:0 0.6rem 1.6rem rgba(0,0,0,0.04)}
		.club-actions{ margin-top:0.75rem }
		.club-form .box, .club-card .box { width:100%; }
		.club-hero { margin-bottom:1.6rem }
		.invite-panel { border:1px solid #e0e0e0; border-radius:10px; padding:1rem; margin-top:1rem; background:#fafafa; }
		.invite-panel label { display:flex; gap:0.6rem; align-items:flex-start; font-weight:400; cursor:pointer; padding:0.35rem 0; border-bottom:1px solid #eee; }
		.invite-panel label:last-child { border-bottom:0; }
		.invite-scroll { max-height:220px; overflow:auto; margin-top:0.5rem; }
		.invite-filter { width:100%; margin-bottom:0.5rem; }
		.my-clubs-bar { margin-bottom:1.5rem; }
		.my-club-card { background:#f8fcf9; border:1px solid #cfe9d4; border-radius:10px; padding:1.1rem 1.25rem; margin-bottom:1rem; }
		.my-club-card h3 { margin:0 0 0.35rem; font-size:1.15rem; }
		.my-club-meta { color:#555; font-size:0.92rem; margin:0 0 0.75rem; }
		.my-read-form { display:grid; gap:0.5rem; margin-top:0.6rem; padding-top:0.75rem; border-top:1px dashed #cde5d4; }
		.my-read-form label { font-size:0.88rem; color:#444; }
		.club-toolbar { display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; margin-top:0.35rem; }
		.btn-danger { background:#c0392b; color:#fff; border:none; cursor:pointer; padding:0.45rem 0.85rem; border-radius:6px; font-size:0.95rem; }
		.btn-danger:hover { filter:brightness(1.05); }
		.role-note { background:#fff8e6; border:1px solid #f0e0b2; border-radius:8px; padding:0.85rem 1rem; margin-bottom:1rem; color:#663; }
		.member-reads-list { margin-top: 0.65rem; padding: 0.65rem 0.85rem; background: #fff; border-radius: 8px; border: 1px solid #e0ebe4; }
		.member-reads-list h4 { margin: 0 0 0.45rem; font-size: 1rem; color: #2e5d3a; }
		.member-reads-list ul { margin: 0; padding-left: 1.15rem; font-size: 0.93rem; line-height: 1.45; }
		.discussions-panel { margin-top: 0.85rem; padding: 0.75rem 0.85rem; background: #fff; border-radius: 8px; border: 1px solid #dce8df; }
		.discussions-panel h4 { margin: 0 0 0.5rem; font-size: 1rem; color: #2e5d3a; }
		.disc-thread { border-bottom: 1px solid #edf3ef; padding: 0.65rem 0; font-size: 0.92rem; }
		.disc-thread:last-child { border-bottom: 0; }
		.disc-thread h5 { margin: 0 0 0.35rem; font-size: 1rem; color: #222; }
		.disc-meta { font-size: 0.82rem; color: #777; margin-bottom: 0.35rem; }
		.disc-body { color: #444; line-height: 1.45; white-space: pre-wrap; word-break: break-word; }
		.disc-actions { margin-top: 0.45rem; display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap; }
		.disc-actions a { font-size: 0.88rem; }
		.disc-new { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #dce8df; }
		.disc-new label { font-size: 0.88rem; color: #444; }
	</style>
</head>
<body>
	<header class="header">
		<div class="header-1">
			<a href="./index.php" class="logo"><i class="fas fa-book"></i> IBRCN</a>
			<div class="icons">
				<?php if ($sessionUserRow): ?>
				<a href="./mailbox.php" class="fas fa-envelope" title="Mail"></a>
				<?php endif; ?>
				<a href="./cart.php" class="fas fa-shopping-cart" title="Cart"></a>
			</div>
		</div>
	</header>

	<main class="container">
		<?php if ($message): ?>
			<div class="alert" style="background:#eef9f0;color:#065;border-radius:8px;padding:12px;margin:12px 0"><?php echo htmlspecialchars($message); ?></div>
		<?php endif; ?>

		<?php if ($sessionUserRow && !$isReader): ?>
			<p class="role-note">Reading clubs are for <strong>Reader</strong> accounts only. You are signed in as <?php echo htmlspecialchars((string) $sessionUserRow['role']); ?> — use a reader profile to create, join, invite, or manage clubs.</p>
		<?php endif; ?>

		<?php if ($isReader && !empty($myClubs)): ?>
			<section class="my-clubs-bar" aria-labelledby="my-clubs-heading">
				<h2 id="my-clubs-heading" style="margin:0 0 0.75rem;font-size:1.35rem">Your reading clubs</h2>
				<p style="margin:0 0 1rem;color:#555;font-size:0.95rem">Everyone in the club can see each member’s current book. Update yours below.</p>
				<?php foreach ($myClubs as $mc): ?>
					<div class="my-club-card">
						<h3><?php echo htmlspecialchars((string) $mc['name']); ?></h3>
						<?php if (!empty($mc['description'])): ?>
							<p class="my-club-meta"><?php echo nl2br(htmlspecialchars((string) $mc['description'])); ?></p>
						<?php endif; ?>
						<?php
						$clubReads = $memberReadsByClub[(int) $mc['club_id']] ?? array();
						?>
						<?php if (!empty($clubReads)): ?>
							<div class="member-reads-list">
								<h4>What members are reading</h4>
								<ul>
									<?php foreach ($clubReads as $mr): ?>
										<li>
											<strong><?php echo htmlspecialchars($mr['display_name']); ?></strong>
											<?php if ($mr['member_user_id'] !== null && (int) $mr['member_user_id'] === $sessionUserId): ?>
												<span style="color:#888;font-size:0.88rem"> (you)</span>
											<?php endif; ?>
											<?php if (!empty($mr['book_title'])): ?>
												— <em><?php echo htmlspecialchars($mr['book_title']); ?></em>
												<?php if (!empty($mr['book_author'])): ?>
													<span style="color:#555"> — <?php echo htmlspecialchars($mr['book_author']); ?></span>
												<?php endif; ?>
											<?php else: ?>
												<span style="color:#999"> — no book listed yet</span>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php
						$cid = (int) $mc['club_id'];
						$clubThreads = $threadsByClub[$cid] ?? array();
						?>
						<div class="discussions-panel" id="discussions-c<?php echo $cid; ?>">
							<h4>Club discussions</h4>
							<p style="margin:0 0 0.65rem;font-size:0.86rem;color:#666">Only members of this club see these threads. You can edit or delete your own posts.</p>
							<?php foreach ($clubThreads as $th): ?>
								<?php
								$tid = (int) $th['thread_id'];
								$isAuthor = (int) $th['user_id'] === $sessionUserId;
								$isEditing = $editThreadId > 0 && $tid === $editThreadId && $isAuthor;
								?>
								<div class="disc-thread">
									<?php if ($isEditing): ?>
										<form method="post" action="member.php">
											<input type="hidden" name="action" value="discussion_update">
											<input type="hidden" name="thread_id" value="<?php echo $tid; ?>">
											<label>Title</label>
											<input type="text" name="disc_title" class="box" required value="<?php echo htmlspecialchars($th['title']); ?>">
											<label style="display:block;margin-top:0.5rem">Message</label>
											<textarea name="disc_body" class="box" rows="5" required><?php echo htmlspecialchars($th['body']); ?></textarea>
											<div class="disc-actions" style="margin-top:0.5rem">
												<button type="submit" class="btn">Save changes</button>
												<a href="member.php#discussions-c<?php echo $cid; ?>">Cancel</a>
											</div>
										</form>
									<?php else: ?>
										<h5><?php echo htmlspecialchars($th['title']); ?></h5>
										<div class="disc-meta">
											<?php echo htmlspecialchars($th['author_name']); ?>
											· <?php echo htmlspecialchars($th['created_at']); ?>
											<?php if ($th['updated_at'] !== $th['created_at']): ?>
												<span style="color:#aaa"> (edited <?php echo htmlspecialchars($th['updated_at']); ?>)</span>
											<?php endif; ?>
										</div>
										<div class="disc-body"><?php echo nl2br(htmlspecialchars($th['body'])); ?></div>
										<?php if ($isAuthor): ?>
											<div class="disc-actions">
												<a href="member.php?edit_thread=<?php echo $tid; ?>#discussions-c<?php echo $cid; ?>">Edit</a>
												<form method="post" action="member.php" style="display:inline;margin:0" onsubmit="return confirm('Delete this discussion?');">
													<input type="hidden" name="action" value="discussion_delete">
													<input type="hidden" name="thread_id" value="<?php echo $tid; ?>">
													<button type="submit" class="btn-danger" style="padding:0.25rem 0.55rem;font-size:0.85rem">Delete</button>
												</form>
											</div>
										<?php endif; ?>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
							<?php if (empty($clubThreads)): ?>
								<p style="margin:0;font-size:0.9rem;color:#888">No threads yet — start one below.</p>
							<?php endif; ?>

							<div class="disc-new">
								<strong style="font-size:0.92rem">New thread</strong>
								<form method="post" action="member.php#discussions-c<?php echo $cid; ?>" style="margin-top:0.5rem">
									<input type="hidden" name="action" value="discussion_create">
									<input type="hidden" name="club_id" value="<?php echo $cid; ?>">
									<label>Title</label>
									<input type="text" name="disc_title" class="box" required placeholder="Topic title">
									<label style="display:block;margin-top:0.5rem">Message</label>
									<textarea name="disc_body" class="box" rows="4" required placeholder="What do you want to discuss?"></textarea>
									<button type="submit" class="btn" style="margin-top:0.5rem">Post discussion</button>
								</form>
							</div>
						</div>

						<div class="club-toolbar">
							<form method="post" action="member.php" onsubmit="return confirm('Leave this club?');">
								<input type="hidden" name="action" value="leave_club">
								<input type="hidden" name="club_id" value="<?php echo (int) $mc['club_id']; ?>">
								<button type="submit" class="btn-danger">Leave club</button>
							</form>
						</div>
						<form method="post" action="member.php" class="my-read-form">
							<input type="hidden" name="action" value="save_current_read">
							<input type="hidden" name="club_id" value="<?php echo (int) $mc['club_id']; ?>">
							<label>Book title (what you are reading)</label>
							<input type="text" name="book_title" class="box" value="<?php echo htmlspecialchars((string) ($mc['my_book_title'] ?? '')); ?>" placeholder="e.g. The Midnight Library">
							<label>Author (optional)</label>
							<input type="text" name="book_author" class="box" value="<?php echo htmlspecialchars((string) ($mc['my_book_author'] ?? '')); ?>" placeholder="e.g. Matt Haig">
							<div><button type="submit" class="btn">Save current read</button></div>
							<p style="margin:0;font-size:0.82rem;color:#777">Clear the title and save to remove your current read for this club.</p>
						</form>
					</div>
				<?php endforeach; ?>
			</section>
		<?php elseif (!$sessionUserRow): ?>
			<p style="color:#666;margin-bottom:1rem"><a href="./login.php">Sign in</a> with a <strong>reader</strong> account to invite other readers and join clubs.</p>
		<?php endif; ?>

		<section class="club-hero">
			<h1 class="heading"><span>Reading Clubs</span></h1>
			<p style="color:#666;max-width:66ch">Create a club or join an existing one. Invites list only <strong>reader</strong> accounts. Sign in as a reader to join — guest email join is disabled so memberships stay tied to reader profiles.</p>
		</section>

		<section class="club-form">
			<div class="row">
				<div class="col-md-6" style="max-width:560px">
					<h2>Create a Club</h2>
					<?php if ($sessionUserRow && !$isReader): ?>
						<p style="color:#666">Switch to a reader account to create a club.</p>
					<?php else: ?>
					<form method="post" action="member.php">
						<input type="hidden" name="action" value="create_club">
						<div class="form-group">
							<label>Club name</label>
							<input type="text" name="club_name" class="box" required>
						</div>
						<div class="form-group mt-2">
							<label>Description</label>
							<textarea name="club_description" class="box" rows="3"></textarea>
						</div>
						<?php if (!$sessionUserRow): ?>
							<div class="form-group mt-2">
								<label>Your email (optional)</label>
								<input type="email" name="email" class="box" placeholder="you@example.com">
							</div>
						<?php endif; ?>

						<?php if ($isReader && !empty($inviteUsers)): ?>
							<div class="invite-panel">
								<strong>Invite members</strong>
								<p style="margin:0.4rem 0 0;font-size:0.92rem;color:#666">Choose other <strong>reader</strong> accounts to add to this club (they will see it on their account).</p>
								<input type="search" class="box invite-filter" id="invite-filter" placeholder="Filter by name or email..." autocomplete="off">
								<div class="invite-scroll" id="invite-list">
									<?php foreach ($inviteUsers as $u): ?>
										<label data-search="<?php echo htmlspecialchars(strtolower($u['username'] . ' ' . $u['email'])); ?>">
											<input type="checkbox" name="invite_user_ids[]" value="<?php echo (int) $u['user_id']; ?>">
											<span><?php echo htmlspecialchars($u['username']); ?> <span style="color:#888;font-size:0.9rem">(<?php echo htmlspecialchars($u['email']); ?>)</span></span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						<?php elseif ($isReader && empty($inviteUsers)): ?>
							<p style="font-size:0.92rem;color:#666;margin-top:0.75rem">No other reader accounts to invite yet.</p>
						<?php endif; ?>

						<div class="mt-3">
							<button class="btn" type="submit">Create Club</button>
							<a class="btn" href="./member.php?view=owner">Owner signup</a>
						</div>
					</form>
					<?php endif; ?>
				</div>
				<div class="col-md-6" style="display:flex;align-items:center;justify-content:center">
					<img src="./img/img5.svg" alt="Reading" style="max-width:320px;opacity:0.95" />
				</div>
			</div>
		</section>

		<section style="margin-top:2rem">
			<h2 class="heading"><span>Available Clubs</span></h2>
			<?php if (empty($clubs)): ?>
				<p style="color:#666">No clubs yet. Create the first club above.</p>
			<?php else: ?>
				<div class="club-grid">
					<?php foreach ($clubs as $c): ?>
						<?php
						$cid = (int) $c['club_id'];
						$alreadyIn = $isReader && in_array($cid, $myClubIds, true);
						?>
						<article class="club-card">
							<h3><?php echo htmlspecialchars($c['name']); ?></h3>
							<div style="color:#666;margin-top:.4rem;line-height:1.35"><?php echo nl2br(htmlspecialchars((string) $c['description'])); ?></div>
							<div style="font-size:.85rem;color:#999;margin-top:.6rem">Created: <?php echo htmlspecialchars((string) $c['created_at']); ?></div>
							<div class="club-actions">
								<?php if ($alreadyIn): ?>
									<p style="margin:0;font-size:0.95rem;color:#2e7d32"><strong>You are a member</strong> — manage above under “Your reading clubs.”</p>
								<?php elseif ($isReader): ?>
									<form method="post" action="member.php" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
										<input type="hidden" name="action" value="join_club">
										<input type="hidden" name="club_id" value="<?php echo $cid; ?>">
										<span style="font-size:0.95rem;color:#555">Join as <?php echo htmlspecialchars((string) $sessionUserRow['username']); ?></span>
										<button class="btn" type="submit">Join</button>
									</form>
								<?php elseif ($sessionUserRow): ?>
									<p style="margin:0;font-size:0.95rem;color:#666">Reader accounts only. <a href="./reader.php">Go to reader home</a> if you used the wrong profile.</p>
								<?php else: ?>
									<p style="margin:0;font-size:0.95rem;color:#666"><a href="./login.php">Sign in</a> with a reader account to join.</p>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

	</main>

	<?php include_once __DIR__ . '/../partials/site_footer.php'; ?>

	<script>
	(function () {
		var f = document.getElementById('invite-filter');
		var box = document.getElementById('invite-list');
		if (!f || !box) return;
		f.addEventListener('input', function () {
			var q = (f.value || '').trim().toLowerCase();
			box.querySelectorAll('label').forEach(function (lab) {
				var hay = lab.getAttribute('data-search') || '';
				lab.style.display = (!q || hay.indexOf(q) !== -1) ? 'flex' : 'none';
			});
		});
	})();
	</script>
</body>
</html>


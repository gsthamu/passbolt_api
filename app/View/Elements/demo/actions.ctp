<?php
/**
 * Demo Actions (Header 3)
 *
 * @copyright     copyright 2013 passbolt.com
 * @license       http://www.passbolt.com/license
 * @package       app.View.Elements.demo.actions
 * @since         version 2.13.02
 */
?>
	<!-- action buttons -->
	<div class="col1 workspace-title-wrapper">
		<h1 class="workspace-title">Passwords</h1>
	</div>
	<div class="col2_3 actions-wrapper">
		<ul class="actions">
			<li>
				<a href="#"  class="button" id="js_action_create">
					<i class="icon create"></i>
					<span>create</span>
				</a>
			</li>
			<li>
				<a href="#" class="button disabled">
					<i class="icon edit"></i>
					<span>edit</span>
				</a>
			</li>
			<li>
				<a href="#" class="button disabled">
					<i class="icon share"></i>
					<span>share</span>
				</a>
			</li>
			<li>
				<div class="dropdown">
					<a href="http://localhost/passbolt/demo/#" class="button">
						<span>more</span>
						<i class="icon after arrowdown"></i>
					</a>
					<ul class="dropdown-content">
						<li><a href="#">copy login to clipboard</a></li>
						<li><a href="#">copy password to clipboard</a></li>
						<li><a href="#">organize</a></li>
						<li><a href="#">review logs</a></li>
					</ul>
				</div>
			</li>
		</ul>
		<ul class="actions secondary">
			<li>
				<a href="#" class="button selected toggle">
					<i class="icon layout eye big no-text"></i>
					<span>view sidebar</span>
				</a>
			</li>
			<li>
				<a href="#" class="button selected toggle duo">
					<i class="icon layout grid big no-text"></i>
					<span>grid layout</span>
				</a>
				<a href="#" class="button toggle duo">
					<i class="icon layout box big no-text"></i>
					<span>box layout</span>
				</a>
			</li>
			<li>
				<div class="dropdown">
					<a href="http://localhost/passbolt/demo/#" class="button">
						<i class="icon cog big no-text"></i>
						<span>config</span>
					</a>
					<ul class="dropdown-content right">
						<li>
							<div class="input checkbox">
								<input type="checkbox" name="select" id="checkbox-00d77ffa-7278-41fc-a4bb-1b63d7a10fee" checked>
								<label for="checkbox-00d77ffa-7278-41fc-a4bb-1b63d7a10fee">resource name</label>
							</div>
						</li>
						<li>
							<div class="input checkbox">
								<input type="checkbox" name="select" id="checkbox-10d77ffa-7278-41fc-a4bb-1b63d7a10fee" checked>
								<label for="checkbox-10d77ffa-7278-41fc-a4bb-1b63d7a10fee">username</label>
							</div>
						</li>
						<li>
							<div class="input checkbox">
								<input type="checkbox" name="select" id="checkbox-20d77ffa-7278-41fc-a4bb-1b63d7a10fee" checked>
								<label for="checkbox-20d77ffa-7278-41fc-a4bb-1b63d7a10fee">password</label>
							</div>
						</li>
						<li>
							<div class="input checkbox">
								<input type="checkbox" name="select" id="checkbox-30d77ffa-7278-41fc-a4bb-1b63d7a10fde" checked>
								<label for="checkbox-30d77ffa-7278-41fc-a4bb-1b63d7a10fde">URL</label>
							</div>
						</li>
						<li>
							<div class="input checkbox">
								<input type="checkbox" name="select" id="checkbox-40d77ffa-7278-41fc-a4bb-1b63d7a10fee">
								<label for="checkbox-40d77ffa-7278-41fc-a4bb-1b63d7a10fee">owner</label>
							</div>
						</li>
						<li>
							<div class="input checkbox">
								<input type="checkbox" name="select" id="checkbox-50d77ffa-7278-41fc-a4bb-1b63d7a10fee">
								<label for="checkbox-50d77ffa-7278-41fc-a4bb-1b63d7a10fee">expire</label>
							</div>
						</li>
						<li>
							<div class="input checkbox">
								<input type="checkbox" name="select" id="checkbox-60d77ffa-7278-41fc-a4bb-1b63d7a10fee">
								<label for="checkbox-60d77ffa-7278-41fc-a4bb-1b63d7a10fee">modified</label>
							</div>
						</li>
						<li>
							<div class="input checkbox">
								<input type="checkbox" name="select" id="checkbox-70d77ffa-7278-41fc-a4bb-1b63d7a10fed">
								<label for="checkbox-70d77ffa-7278-41fc-a4bb-1b63d7a10fed">created</label>
							</div>
						</li>
						<li><a href="#" class="top-separator">save this settings</a></li>
						<li><a href="#">reset to default setting</a></li>
					</ul>
				</div>
			</li>
		</ul>
	</div>
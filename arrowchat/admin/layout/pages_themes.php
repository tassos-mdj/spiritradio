			<div class="title_bg"> 
				<div class="title">Themes</div> 
				<div class="module_content">
					<form method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>?do=<?php echo $do; ?>" enctype="multipart/form-data">
<?php
	if ($do == "edit") 
	{
		if (!empty($msg)) 
		{
?>
					<a href="themes.php?do=managethemes">Click here to go back to themes management</a>
<?php
		} 
		else 
		{
			$result = $db->execute("
				SELECT * 
				FROM arrowchat_themes 
				WHERE id = '" . $db->escape_string(get_var('id')) . "'
			");

			if ($result AND $db->count_select() > 0) 
			{
				$row = $db->fetch_array($result);
?>
					<div class="subtitle">Edit Theme</div>
					<fieldset class="firstFieldset">
						<dl class="selectionBox">
							<dt>
								<label for="theme_name">Theme Name</label>
							</dt>
							<dd>
								<input type="text" id="theme_name" class="selectionText" name="theme_name" value="<?php echo $row['name']; ?>" />
								<p class="explain">
									Provide a name for the theme.
								</p>
							</dd>
						</dl>
						<dl class="selectionBox">
							<dt>
								<label for="theme_folder">Theme Folder</label>
							</dt>
							<dd>
								<input type="text" id="theme_folder" class="selectionText" name="theme_folder" value="<?php echo $row['folder']; ?>" />
								<p class="explain">
									The folder location of the theme. You should not have to change this.
								</p>
							</dd>
						</dl>
					</fieldset>
					<fieldset>
						<dl class="selectionBox">
							<dt></dt>
							<dd>
								<ul>
									<li>
										<label for="theme_default">
											<input type="checkbox" id="theme_default" name="theme_default" <?php if ($row['default']==1) { echo 'checked="checked"'; echo " disabled='disabled'"; } ?> value="1" />
											Make Active
										</label>
									</li>
								</ul>
								<p class="explain">
									Check this to make this the new active theme.
								</p>
							</dd>
						</dl>
					</fieldset>
					<dl class="selectionBox submitBox">
						<dt></dt>
						<dd>
							<div class="floatr">
								<a class="fwdbutton" onclick="document.forms[0].submit(); return false">
									<span>Save Changes</span>
								</a>
								<input type="hidden" name="theme_edit_submit" value="1" />
								<input type="hidden" name="theme_id" value="<?php echo $row['id']; ?>" />
								<?php if ($row['default']==1) { ?>
								<input type="hidden" name="theme_active" value="1" />
								<input type="hidden" name="theme_default" value="1" />
								<?php } ?>
							</div>
						</dd>
					</dl>
<?php
			} 
			else 
			{
?>
				This theme does not exist.
<?php
			}
		}
	}
?>
<?php
	if ($do == "color") 
	{
		if (!empty($msg)) 
		{
?>
					<a href="themes.php?do=managethemes">Click here to go back to themes management</a>
<?php
		} 
		else 
		{
			$result = $db->execute("
				SELECT * 
				FROM arrowchat_themes 
				WHERE id = '" . $db->escape_string(get_var('id')) . "'
			");

			if ($result AND $db->count_select() > 0) 
			{
				$row = $db->fetch_array($result);
				
				$primary_color = "";
				$secondary_color = "";
				$button_background = "";
				$button_icon = "";
				$chat_bubble = "";
				$chat_bubble_text = "";
				$chat_bubble_self = "";
				$chat_bubble_self_text = "";
				
				if (file_exists(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . AC_FOLDER_THEMES . DIRECTORY_SEPARATOR . $row['folder'] . '/css/custom_css.php'))
				{
					if (file_exists(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . AC_FOLDER_CACHE . DIRECTORY_SEPARATOR . 'style_' . $row['folder'] . '.php'))
					{
						include_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . AC_FOLDER_CACHE . DIRECTORY_SEPARATOR . 'style_' . $row['folder'] . '.php');
					}
?>
					<link rel="stylesheet" href="includes/css/colorpicker.css" type="text/css" media="screen">
					<script type="text/javascript" src="includes/js/colorpicker.js"></script> 
					<script type="text/javascript">
						function rgbtohsl(a, b, c) {
							a /= 255, b /= 255, c /= 255;
							var d = Math.max(a, b, c), e = Math.min(a, b, c);
							var f, g, h = (d + e) / 2;
							if (d == e) f = g = 0; else {
								var i = d - e;
								g = h > .5 ? i / (2 - d - e) : i / (d + e);
								switch (d) {
								  case a:
									f = (b - c) / i + (b < c ? 6 : 0);
									break;

								  case b:
									f = (c - a) / i + 2;
									break;

								  case c:
									f = (a - b) / i + 4;
								}
								f /= 6;
							}
							return [ f, g, h ];
						}
						function hsltorgb(a, b, c) {
							var d, e, f;
							if (0 == b) d = e = f = c; else {
								function g(a, b, c) {
									if (c < 0) c += 1;
									if (c > 1) c -= 1;
									if (c < 1 / 6) return a + 6 * (b - a) * c;
									if (c < 1 / 2) return b;
									if (c < 2 / 3) return a + (b - a) * (2 / 3 - c) * 6;
									return a;
								}
								var h = c < .5 ? c * (1 + b) : c + b - c * b;
								var i = 2 * c - h;
								d = g(i, h, a + 1 / 3);
								e = g(i, h, a);
								f = g(i, h, a - 1 / 3);
							}
							return [ 255 * d, 255 * e, 255 * f ];
						}
						function rgbtohex(a, b, c) {
							return "#" + ((1 << 24) + (a << 16) + (b << 8) + c).toString(16).slice(1);
						}
						function hextorgb(a) {
							var b = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(a);
							return b ? {
								r: parseInt(b[1], 16),
								g: parseInt(b[2], 16),
								b: parseInt(b[3], 16)
							} : null;
						}
						function shift(a) {
							$("div.colors").each(function() {
								var b = $(this).attr("oldcolor");
								var c = hextorgb(b);
								var d = rgbtohsl(c.r, c.g, c.b);
								d[0] += parseFloat(a);
								while (d[0] > 1) d[0] -= 1;
								c = hsltorgb(d[0], d[1], d[2]);
								b = rgbtohex(parseInt(c[0]), parseInt(c[1]), parseInt(c[2]));
								$(this).attr("newcolor", b);
								$(this).css("background", b);
							});
						}
						$(document).ready(function() {
							$( "#slider" ).slider({
								value:0,
								min: 0,
								max: 1,
								step: 0.0001,
								slide: function( event, ui ) {
									shift(ui.value);
								}
							});
							$('.style_color_picker').ColorPicker({
								onSubmit: function(hsb, hex, rgb, el) {
									$(el).val(hex);
									$(el).ColorPickerHide();
								},
								onBeforeShow: function() {
									$(activeID).ColorPickerSetColor(this.value);
								},
								onChange: function (hsb, hex, rgb) {
									$(activeID).css('backgroundColor', '#' + hex);
									$(activeID).val(hex);
								}
							}).bind('keyup', function() {
								$(activeID).ColorPickerSetColor(this.value);
								$(activeID).css('backgroundColor', '#' + this.value);
							}).bind('focus', function() {
								activeID = '#' + $(this).attr('id');
							});
							var activeID = "";
							$('#primary_color').css('backgroundColor', '#<?php echo $primary_color; ?>');
							$('#secondary_color').css('backgroundColor', '#<?php echo $secondary_color; ?>');
							$('#button_background').css('backgroundColor', '#<?php echo $button_background; ?>');
							$('#button_icon').css('backgroundColor', '#<?php echo $button_icon; ?>');
							$('#chat_bubble').css('backgroundColor', '#<?php echo $chat_bubble; ?>');
							$('#chat_bubble_text').css('backgroundColor', '#<?php echo $chat_bubble_text; ?>');
							$('#chat_bubble_self').css('backgroundColor', '#<?php echo $chat_bubble_self; ?>');
							$('#chat_bubble_self_text').css('backgroundColor', '#<?php echo $chat_bubble_self_text; ?>');
						});
					</script>
<!--					<div class="subtitle">Color Theme</div>
					<fieldset class="firstFieldset">
						<dl class="selectionBox">
							<dt>
								<label for="theme_name">Theme Name</label>
							</dt>
							<dd style="margin-top:7px">
								<?php echo $row['name']; ?>
							</dd>
						</dl>
					</fieldset>
					<fieldset>
						<dl class="selectionBox">
							<dt>
								<label>Automatic Color Changer</label>
							</dt>
							<dd>
								<div class="themeBox colors" oldcolor="#EEEEEE" newcolor="#EEEEEE" style="background:#EEEEEE;"></div><div class="themeBox colors" oldcolor="#FFFFFF" newcolor="#FFFFFF" style="background:#FFFFFF;"></div><div class="themeBox colors" oldcolor="#CCCCCC" newcolor="#CCCCCC" style="background:#CCCCCC;"></div><div class="themeBox colors" oldcolor="#333333" newcolor="#333333" style="background:#333333;"></div><div class="themeBox colors" oldcolor="#00A7E9" newcolor="#00A7E9" style="background:#00A7E9;"></div><div class="themeBox colors" oldcolor="#304650" newcolor="#304650" style="background:#304650;"></div><div class="themeBox colors" oldcolor="#111111" newcolor="#111111" style="background:#111111;"></div><div class="themeBox colors" oldcolor="#666666" newcolor="#666666" style="background:#666666;"></div><div class="themeBox colors" oldcolor="#4BBCE8" newcolor="#4BBCE8" style="background:#4BBCE8;"></div><div class="themeBox colors" oldcolor="#EBEBEB" newcolor="#EBEBEB" style="background:#EBEBEB;"></div><div class="themeBox colors" oldcolor="#E85871" newcolor="#E85871" style="background:#E85871;"></div><div class="themeBox colors" oldcolor="#FF2B33" newcolor="#FF2B33" style="background:#FF2B33;"></div><div class="themeBox colors" oldcolor="#5D5D5D" newcolor="#5D5D5D" style="background:#5D5D5D;"></div><div class="themeBox colors" oldcolor="#C0C0C0" newcolor="#C0C0C0" style="background:#C0C0C0;"></div><div class="themeBox colors" oldcolor="#6E6E6E" newcolor="#6E6E6E" style="background:#6E6E6E;"></div><div class="themeBox colors" oldcolor="#595959" newcolor="#595959" style="background:#595959;"></div><div class="themeBox colors" oldcolor="#1C97D0" newcolor="#1C97D0" style="background:#1C97D0;"></div><div class="themeBox colors" oldcolor="#868686" newcolor="#868686" style="background:#868686;"></div>
								<div style="clear:both;padding:7.5px;"></div>
								<div id="slider"></div>
							</dd>
						</dl>
					</fieldset>
					<dl class="selectionBox submitBox">
						<dt></dt>
						<dd>
							<div class="floatr">
								<a class="fwdbutton" onclick="document.forms[0].submit(); return false">
									<span>Save Changes</span>
								</a>
								<input type="hidden" name="theme_color_submit" value="1" />
								<input type="hidden" name="theme_id" value="<?php echo $row['folder']; ?>" />
							</div>
						</dd>
					</dl>
					<input type="hidden" name="token" value="<?php echo $token; ?>" />
					</form>
				</div>
			</div>
			<div class="title_bg"> 
				<div class="title">Custom Colors</div> 
				<div class="module_content"> -->
					<div class="subtitle">Custom Colors</div>
					<div class="subExplain"><i>All colors should be in <strong>hex format without the leading #</strong>. Leave blank for no change to the default color.  This is a basic tool for beginner users; please use the CSS file for more advanced control.</i></div>
					<fieldset class="firstFieldset">
						<?php if (ARROWCHAT_EDITION == "free") { ?><div class="unlock-feature">Unlock this feature by purchasing an <a href="https://www.arrowchat.com/purchase/" target="_blank">ArrowChat license</a></div><?php } ?>
						<div class="<?php if (ARROWCHAT_EDITION == "free") echo 'version-disabled'; ?>">
						<dl class="selectionBox">
							<dt>
								<label for="primary_color">Primary Color</label>
							</dt>
							<dd>
								<input type="text" id="primary_color" class="selectionText style_color_picker" name="primary_color" value="<?php echo $primary_color; ?>" />
								<p class="explain">
								The main color of the chat (icons, buttons, etc)
								</p>
							</dd>
						</dl>
						<dl class="selectionBox">
							<dt>
								<label for="secondary_color">Secondary Color</label>
							</dt>
							<dd>
								<input type="text" id="secondary_color" class="selectionText style_color_picker" name="secondary_color" value="<?php echo $secondary_color; ?>" />
								<p class="explain">
								The second color should be slightly lighter than the main, mostly used for hovers
								</p>
							</dd>
						</dl>
						</div>
					</fieldset>
					<fieldset>
						<?php if (ARROWCHAT_EDITION == "free") { ?><div class="unlock-feature">Unlock this feature by purchasing an <a href="https://www.arrowchat.com/purchase/" target="_blank">ArrowChat license</a></div><?php } ?>
						<div class="<?php if (ARROWCHAT_EDITION == "free") echo 'version-disabled'; ?>">
						<dl class="selectionBox">
							<dt>
								<label for="button_background">Main Button Background</label>
							</dt>
							<dd>
								<input type="text" id="button_background" class="selectionText style_color_picker" name="button_background" value="<?php echo $button_background; ?>" />
								<p class="explain">
								The background color of the button to open ArrowChat
								</p>
							</dd>
						</dl>
						<dl class="selectionBox">
							<dt>
								<label for="button_icon">Main Button Icon</label>
							</dt>
							<dd>
								<input type="text" id="button_icon" class="selectionText style_color_picker" name="button_icon" value="<?php echo $button_icon; ?>" />
								<p class="explain">
								The icon color of the button to open ArrowChat
								</p>
							</dd>
						</dl>
						</div>
					</fieldset>
					<fieldset>
						<?php if (ARROWCHAT_EDITION == "free") { ?><div class="unlock-feature">Unlock this feature by purchasing an <a href="https://www.arrowchat.com/purchase/" target="_blank">ArrowChat license</a></div><?php } ?>
						<div class="<?php if (ARROWCHAT_EDITION == "free") echo 'version-disabled'; ?>">
						<dl class="selectionBox">
							<dt>
								<label for="chat_bubble">Chat Bubble</label>
							</dt>
							<dd>
								<input type="text" id="chat_bubble" class="selectionText style_color_picker" name="chat_bubble" value="<?php echo $chat_bubble; ?>" />
								<p class="explain">
								Background for text messages
								</p>
							</dd>
						</dl>
						<dl class="selectionBox">
							<dt>
								<label for="chat_bubble_text">Chat Bubble Text</label>
							</dt>
							<dd>
								<input type="text" id="chat_bubble_text" class="selectionText style_color_picker" name="chat_bubble_text" value="<?php echo $chat_bubble_text; ?>" />
								<p class="explain">
								Text color for messages
								</p>
							</dd>
						</dl>
						<dl class="selectionBox">
							<dt>
								<label for="chat_bubble_self">Chat Bubble Self</label>
							</dt>
							<dd>
								<input type="text" id="chat_bubble_self" class="selectionText style_color_picker" name="chat_bubble_self" value="<?php echo $chat_bubble_self; ?>" />
								<p class="explain">
								Background for messages sent by same user
								</p>
							</dd>
						</dl>
						<dl class="selectionBox">
							<dt>
								<label for="chat_bubble_self_text">Chat Bubble Self Text</label>
							</dt>
							<dd>
								<input type="text" id="chat_bubble_self_text" class="selectionText style_color_picker" name="chat_bubble_self_text" value="<?php echo $chat_bubble_self_text; ?>" />
								<p class="explain">
								Text color for messages sent by same user
								</p>
							</dd>
						</dl>
						</div>
					</fieldset>
					<dl class="selectionBox submitBox">
						<dt></dt>
						<dd>
							<div class="floatr">
								<?php if (ARROWCHAT_EDITION != "free") { ?>
								<a class="fwdbutton" onclick="document.forms[0].submit(); return false">
									<span>Save Changes</span>
								</a>
								<?php } ?>
								<input type="hidden" name="custom_colors_submit" value="1" />
								<input type="hidden" name="theme_id" value="<?php echo $row['folder']; ?>" />
							</div>
						</dd>
					</dl>
					<input type="hidden" name="token" value="<?php echo $token; ?>" />
					</form>
				</div>
			</div>
<?php
				}
				else
				{
?>
					This theme cannot be colorized.
<?php
				}
			}
			else 
			{
?>
				This theme does not exist.
<?php
			}
		}
	}
?>
<?php
	if ($do == "install") 
	{
		if (!empty($msg)) 
		{
?>
					<a href="themes.php?do=managethemes">Click here to go back to themes management</a>
<?php
		} 
		else 
		{
?>
					<div class="subtitle">Install Theme</div>
					<fieldset class="firstFieldset">
						<dl class="selectionBox">
							<dt>
								<label for="theme_name">Theme Name</label>
							</dt>
							<dd>
								<input type="text" id="theme_name" class="selectionText" name="theme_name" value="<?php if (!empty($_REQUEST['theme_name'])) { echo get_var('theme_name'); } ?>" />
								<p class="explain">
									Provide a name for the theme.
								</p>
							</dd>
						</dl>
						<dl class="selectionBox">
							<dt>
								<label for="theme_folder">Theme Folder</label>
							</dt>
							<dd>
								<input type="text" id="theme_folder" class="selectionText" name="theme_folder" value="<?php if (!empty($_REQUEST['theme_folder'])) { echo get_var('theme_folder'); } else { echo get_var('f'); } ?>" />
								<p class="explain">
									The folder location of the theme. You should not have to change this.
								</p>
							</dd>
						</dl>
					</fieldset>
					<fieldset>
						<dl class="selectionBox">
							<dt></dt>
							<dd>
								<ul>
									<li>
										<label for="theme_default">
											<input type="checkbox" id="theme_default" name="theme_default" value="1" />
											Make Active
										</label>
									</li>
								</ul>
								<p class="explain">
									Check this if you would like for this to be the active theme.
								</p>
							</dd>
						</dl>
					</fieldset>
					<dl class="selectionBox submitBox">
						<dt></dt>
						<dd>
							<div class="floatr">
								<a class="fwdbutton" onclick="document.forms[0].submit(); return false">
									<span>Install Theme</span>
								</a>
								<input type="hidden" name="theme_install_submit" value="1" />
							</div>
						</dd>
					</dl>
<?php
		}
	}
?>
<?php
	if ($do == "smilies") 
	{
?>
					<script type="text/javascript">
						$(document).ready(function() {
							$("#add_smiley_name").keyup(function () {
								$("#add_smiley_path").html("includes/emojis/img/32/" + $(this).val());
								$("#add_smiley_path2").html("includes/emojis/img/16/" + $(this).val());
							});
						});
					</script>
					<div class="subtitle">Emojis</div>
					<div class="subExplain"><i>The filename is the name of the file in the includes/emojis/32/ folder.  All emojis are square, 32x32 and 16x16.  You must also place the emoji in the 16 folder (for 16x16).</i></div>
					<table cellspacing="0" cellpadding="0" class="table_table">
						<tr>
							<th>Emoji Filename</th>
							<th>Emoji Shortcut</th>
							<th></th>
						</tr>
<?php
	$result = $db->execute("
		SELECT * 
		FROM arrowchat_smilies
	");

	$i = 1;
	while ($row = $db->fetch_array($result)) 
	{
?>
						<tr>
							<td>
								<input type="hidden" name="smiley_id_<?php echo $i; ?>" value="<?php echo $row['id']; ?>" />
								<input type="text" class="selectionText" style="width: 280px;" name="smiley_name_<?php echo $i; ?>" value="<?php echo $row['name']; ?>" />
							</td>
							<td><input type="text" class="selectionText" style="width: 280px;" name="smiley_pattern_<?php echo $i; ?>" value="<?php echo $row['code']; ?>" /></td>
							<td><a href="themes.php?do=smilies&deletesmiley=<?php echo $row['id']; ?>&token=<?php echo $token; ?>"><img style="position:relative; top: 4px;" src="./images/img-red-no.png" title="Delete This Smiley" alt="" border="0" /></a></td>
						</tr>
<?php
		$i++;
	}
?>
					</table>
					<dl class="selectionBox submitBox">
						<dt></dt>
						<dd>
							<div class="floatr" style="float: right;">
								<a class="fwdbutton" onclick="document.forms[0].submit(); return false">
									<span>Save Changes</span>
								</a>
								<input type="hidden" name="smiley_count" value="<?php echo $i; ?>" />
								<input type="hidden" name="smiley_submit" value="1" />
							</div>
						</dd>
					</dl>
					<input type="hidden" name="token" value="<?php echo $token; ?>" />
					</form>

				</div>
			</div>
			<div class="title_bg"> 
				<div class="title">Smilies</div> 
				<div class="module_content">
					<form method="post" id="smiley_add" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>?do=<?php echo $do; ?>" enctype="multipart/form-data">
					<div class="subtitle">Add Emoji</div>
					<fieldset class="firstFieldset">
					<dl class="selectionBox">
						<dt>
							<label for="add_smiley_name">Emoji Filename</label>
						</dt>
						<dd>
							<input type="text" id="add_smiley_name" class="selectionText" name="add_smiley_name" value="" />
							<p class="explain">
								This is the filename of the emoji in the includes/emojis/img/32/ and includes/emojis/img/16/ folders.  <b>You must include the extension!</b>
							</p>
						</dd>
					</dl>
					<dl class="selectionBox">
						<dt>
							<label for="add_smiley_path">Emoji Path</label>
						</dt>
						<dd>
							<div id="add_smiley_path" style="min-height: 20px; margin-top: 5px;">includes/emojis/img/32/</div>
							<div id="add_smiley_path2" style="min-height: 20px; margin-top: 5px;">includes/emojis/img/16/</div>
							<p class="explain">
								These are the paths that your emoji must be placed in.  All emojis are square.  The 32 folder displays 32x32 emojis and the 16 folder displays 16x16 emojis.
							</p>
						</dd>
					</dl>
					<dl class="selectionBox">
						<dt>
							<label for="add_smiley_pattern">Emoji Shortcut</label>
						</dt>
						<dd>
							<input type="text" id="add_smiley_pattern" class="selectionText" name="add_smiley_pattern" value="" />
							<p class="explain">
								This is the code that will be matched in text to convert to the emoji image.
							</p>
						</dd>
					</dl>
					<dl class="selectionBox submitBox">
						<dt></dt>
						<dd>
							<div class="floatr">
								<a class="fwdbutton" onclick="document.forms['smiley_add'].submit(); return false">
									<span>Add Emoji</span>
								</a>
								<input type="hidden" name="add_smiley_submit" value="1" />
							</div>
						</dd>
					</dl>
<?php
	}
?>
<?php
	if ($do == "managethemes") 
	{
?>
					<script type='text/javascript'>
						var tip="";
						$(window).load(function() {
							$(document).click( function(e) {
								if (!$(e.target).hasClass('version_link')) {
									$('.itip-tooltip').fadeOut("fast").remove();
									tip = "";
								}
							});
							$('.version_link').click(function() {
								var c = $(this).attr("id").substr(5);
								if (tip != c && tip != "") {
									$('.itip-tooltip').fadeOut("fast").remove();
								}
								if (tip != c) {
									tip = c;
									$(this).iTip({
										'closeButton' : 'false',
										'direction' : 'right',
										'icons' : [
											{
												'id' : 'close',
												'click' : function()
												{
													tip = "";
												}
											},
											{
												'id' : 'download',
												'click' : function()
												{
													window.open('http://www.arrowchat.com/members/store.php?do=purchases','new');
												}
											},
											{
												'id' : 'save',
												'click' : function()
												{
													document.location = 'themes.php?do=managethemes&update=1&id='+c+'&token=<?php echo $token; ?>';
												}
											}
										]
									})
									$("#close").remove();
								} else {
									$('.itip-tooltip').fadeOut("fast").remove();
									tip = "";
								}
							});
						});
					</script>
					<div class="subtitle">Installed Themes <div class="floatr" style="float:right;position:relative;top:-5px"><a style="height:auto;line-height:28px;padding:0 10.72px" href="https://www.arrowchat.com/store/search/?c=theme" target="_blank" class="fwdbutton"><span style="font-weight:normal;font-size:13px">Get more themes</span></a></div></div>
					<ol class="scrollable">
<?php
		$theme_array = array();
		
		$result = $db->execute("
			SELECT * 
			FROM arrowchat_themes
		");

		while ($row = $db->fetch_array($result)) 
		{
			if ($row['default'] == 1) 
			{
				$used_by = $db->count_rows("
					SELECT theme 
					FROM arrowchat_status 
					WHERE theme = '" . $row['id'] . "' 
						OR theme IS NULL
				");
			} 
			else 
			{
				$used_by = $db->count_rows("
					SELECT theme 
					FROM arrowchat_status 
					WHERE theme = '" . $row['id'] . "'
				");
			}
			
			$theme_array[] = $row['folder'];
			
			if (empty($row['update_link']) OR empty($row['version'])) 
			{
				if (empty($row['version'])) 
				{
					$row['version'] = "1.0";
				}
				
				$current_version = $row['version'];
			} 
			else 
			{
				$fp = @fopen($row['update_link'], "r");
				
				if ($fp) 
				{
					$current_version = @stream_get_contents($fp);
					fclose ($fp);
				} 
				else 
				{
					$current_version = $row['version'];
				}
			}
?>
						<li class="listItem">
							<a href="themes.php?do=managethemes&delete=<?php echo $row['id']; ?>&token=<?php echo $token; ?>" title="Delete" class="secondaryContent delete"><span>Delete</span></a>
							<a href="themes.php?do=edit&id=<?php echo $row['id']; ?>" class="secondaryContent">Edit</a>
							<?php
								if (file_exists(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . AC_FOLDER_THEMES . DIRECTORY_SEPARATOR . $row['folder'] . '/css/custom_css.php')) {
							?>
							<a href="themes.php?do=color&id=<?php echo $row['id']; ?>" class="secondaryContent">Colorize</a>
							<?php
								} else {
							?>
							<a href="javascript:void(0);" class="secondaryContent no-settings">Colorize</a>
							<?php
								}
							?>
							<a href="javascript:;" <?php if ($row['version'] != $current_version) echo "id='itip_".$row['id']."'"; ?> class="secondaryContent version_link <?php if ($row['version'] != $current_version) echo "red"; ?>">v<?php echo $row['version']; ?></a>
							<label title="Make this theme active" class="secondaryContent"><input name="theme_default" onclick="document.forms[0].submit(); return false" type="radio" <?php if ($row['default']==1) echo "checked='checked'"; else echo ""; ?> value="<?php echo $row['id']; ?>" /></label>
							<h4>
								<a href="themes.php?do=edit&id=<?php echo $row['id']; ?>">
									<?php echo $row['name']; ?>
								</a>
							</h4>
						</li>
<?php
		}
?>
					</ol>
					<input type="hidden" name="token" value="<?php echo $token; ?>" />
					<input type="hidden" name="theme_default_submit" value="1" />
					</form>

				</div>
			</div>
			<div class="title_bg"> 
				<div class="title">Themes</div> 
				<div class="module_content">
					<div class="subtitle">Uninstalled Themes</div>
					<ol class="scrollable">
<?php
		$folders = get_folders(AC_FOLDER_THEMES);
		$no_installed = true;
		
		foreach ($folders as $folder) 
		{
			if (!in_array($folder['name'], $theme_array)) 
			{
				$no_installed = false;
?>
							<li class="listItem">
								<a href="themes.php?do=install&f=<?php echo $folder['name']; ?>&token=<?php echo $token; ?>" class="secondaryContent"><span>Install</span></a>
								<h4>
									<a href="javascript:void(0);">
										<?php echo $folder['name']; ?>
									</a>
								</h4>
							</li>
<?php
			}
		}
		
		if ($no_installed) 
		{
?>
						<li class="listItem">
							<h4>
								<a href="#">
									No Uninstalled Themes
								</a>
							</h4>
						</li>
<?php
		}
?>
						</ol>
<?php
	}
?>
					<input type="hidden" name="token" value="<?php echo $token; ?>" />
					</form>

				</div>
			</div>
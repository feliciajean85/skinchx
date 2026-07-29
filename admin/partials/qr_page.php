 <style>
           .copy-btn {
      background: #007aff;
      border: none;
      color: white;
      padding: 8px 14px;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.2s;
      font-size: 14px;
    }
    .copy-btn:hover {
      background: #005ecb;
    }
    .copy-btn:active {
      background: #004da5;
    }
    .copied {
      font-size: 12px;
      color: green;
      margin-left: 5px;
      display: none;
    }
  </style>
         <div class="wrap">
         <h1 style="margin-bottom:20px;">iShortn</h1>

        <div class="card" style="max-width:600px; padding:20px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-radius:8px;">
        <form method="post" action="options.php">
            <?php
            settings_fields('qrcode-tiger_api_group');
            do_settings_sections('qrcode-tiger_api_group');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Username</th>
                    <td><input type="text" id="username" name="qrcode-tiger_username"
                        value="<?php echo esc_attr(get_option('qrcode-tiger_username')); ?>" class="regular-text" />
                         <button type="button" class="copy-btn" onclick="copyText('username', this)">Copy</button>
    <span class="copied">Copied!</span>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Password</th>
                    <td><input type="text" id="password" name="qrcode-tiger_password"
                        value="<?php echo esc_attr(get_option('qrcode-tiger_password')); ?>" class="regular-text" />
                        <button type="button" class="copy-btn" onclick="copyText('password', this)">Copy</button>
    <span class="copied">Copied!</span>
                        </td>
                </tr>
            </table>
            <?php submit_button('Save Credentials'); ?>
        </form>
 <div class="card wp-core-ui " style="margin-top:20px; max-width:600px; padding:20px; text-align:center; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-radius:8px;">
       <a href="https://www.ishortn.ink/auth/sign-in" class="button button-primary btn" target="_blank">Login</a>
     </div>
    </div>
</div>

 <script>
    function copyText(fieldId, btn) {
      const input = document.getElementById(fieldId);
      input.select();
      input.setSelectionRange(0, 99999); // for mobile support
      navigator.clipboard.writeText(input.value).then(() => {
        const copiedMsg = btn.nextElementSibling;
        copiedMsg.style.display = "inline";
        setTimeout(() => {
          copiedMsg.style.display = "none";
        }, 1500);
      });
    }
  </script>
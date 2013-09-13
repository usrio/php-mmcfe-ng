          <ul id="nav">
            <li><a href="/">Home</a></li>
            {if $smarty.session.AUTHENTICATED|default:"0" == 1}
            <li><a href="/?page=account&action=edit">My Account</a>
              <ul>
                <li><a href="/?page=account&action=edit">Edit Account</a></li>
                <li><a href="/?page=account&action=workers">My Workers</a></li>
                <li><a href="/?page=account&action=transactions">Transactions</a></li>
                <li><a href="/?page=account&action=notifications">Notifications</a></li>
                {if !$GLOBAL.config.disable_invitations}<li><a href="/?page=account&action=invitations">Invitations</a></li>{/if}
                <li><a href="{$smarty.server.PHP_SELF}?page=account&action=qrcode">QR Codes</a></li>
              </ul>
            </li>
            {/if}
            {if $smarty.session.AUTHENTICATED|default:"0" == 1 && $GLOBAL.userdata.is_admin == 1}
            <li><a href="/?page=admin">Admin Panel</a>
              <ul>
                <li><a href="/?page=admin&action=monitoring">Monitoring</a></li>
                <li><a href="/?page=admin&action=user">User Info</a></li>
                <li><a href="/?page=admin&action=wallet">Wallet Info</a></li>
                <li><a href="/?page=admin&action=transactions">Transactions</a></li>
                <li><a href="/?page=admin&action=settings">Settings</a></li>
                <li><a href="/?page=admin&action=news">News</a></li>
              </ul>
            </li>
            {/if}
            {if $smarty.session.AUTHENTICATED|default}
            <li><a href="/?page=statistics&action=pool">Statistics</a>
              <ul>
                <li><a href="/?page=statistics&action=pool">Pool Stats</a></li>
                <li><a href="/?page=statistics&action=blocks">Block Stats</a></li>
                <li><a href="/?page=statistics&action=graphs">Hashrate Graphs</a></li>
                <li><a href="{$smarty.server.PHP_SELF}?page=statistics&action=round">Round Stats</a></li>
 		<li><a href="/?page=about&action=donors">Pool Donors</a></li>
              </ul>
            </li>
            {else}
            <li><a href="/?page=statistics">Statistics</a>
              <ul>
            {if $GLOBAL.acl.pool.statistics}
                <li><a href="{$smarty.server.PHP_SELF}?page=statistics&action=pool">Pool Stats</a></li>
            {/if}
            {if $GLOBAL.acl.block.statistics}
                <li><a href="{$smarty.server.PHP_SELF}?page=statistics&action=blocks">Block Stats</a></li>
            {/if}
            {if $GLOBAL.acl.round.statistics}
                <li><a href="{$smarty.server.PHP_SELF}?page=statistics&action=round">Round Stats</a></li>
            {/if}
              </ul>
            {/if}
            <li><a href="/?page=gettingstarted">Getting Started</a></li>
            <li><a href="/?page=support">Support</a>
	      <ul>
                <li><a href="/?page=about&action=api">API Reference</a></li>
		<li><a href="/?page=about&action=pplns">About PPLNS</a></li>
              </ul>

            </li>
            {if $smarty.session.AUTHENTICATED|default == 1}<li><a href="/?page=logout">Logout</a></li>{else}<li><a href="/?page=register">Register</a></li>{/if}
          </ul>

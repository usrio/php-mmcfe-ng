 <article class="module width_quarter">
   <header><h3>{$GLOBAL.config.payout_system|capitalize} Stats</h3></header>
   <div class="module_content">
     <table width="100%">
       <tbody>
{if $GLOBAL.config.payout_system == 'pplns'}
         <tr>
           <td><b>PPLNS Target</b></td>
           <td id="b-pplns" class="right"></td>
         </tr>
{elseif $GLOBAL.config.payout_system == 'pps'}
        <tr>
          <td><b>PPS Value</b></td>
          <td>{$GLOBAL.ppsvalue}</td>
        </tr>
        <tr>
          <td><b>PPS Difficulty</b></td>
          <td id="b-ppsdiff">{$GLOBAL.userdata.sharedifficulty|number_format:"2"}</td>
        </tr>
{/if}
         <tr><td colspan="2">&nbsp;</td></tr>
         {include file="dashboard/round_shares.tpl"}
         <tr><td colspan="2">&nbsp;</td></tr>
         {include file="dashboard/payout_estimates.tpl"}
         <tr><td colspan="2">&nbsp;</td></tr>
         {include file="dashboard/network_info.tpl"}
         <tr><td colspan="2">&nbsp;</td></tr>
       </tbody>
      </table>
    </div>
 </article>


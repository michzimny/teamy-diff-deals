<table class="handrecord">
    <tr>
        <td>
            <h4>
                <?php echo $this->dealer; ?><br>
                <?php echo $this->vuln; ?>
            </h4>
        </td>
        <td><?php echo $this->format_hand(0); ?></td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td><?php echo $this->format_hand(3); ?></td>
        <td><img src="images/<?php echo ($this->deal_num)%16 ? ($this->deal_num)%16 : 16; ?>.gif"
            alt="<?php echo $this->dealer.'/'.$this->vuln; ?>" /></td>
        <td><?php echo $this->format_hand(1); ?></td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td><?php echo $this->format_hand(2); ?></td>
        <td>&nbsp;</td>
    </tr>
    <?php if($this->ability): ?>
    <tr><td colspan="5">
        <small class="sm"><?php echo Protocol::__("Maksymalna liczba lew"); ?>:</small>
        <table class="ability" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td class="an2">&nbsp;</td>
            <td class="an3"><img src="images/N.gif" alt="NT" /></td>
            <td class="an3"><img src="images/S.gif" alt="S" /></td>
            <td class="an3"><img src="images/H.gif" alt="H" /></td>
            <td class="an3"><img src="images/D.gif" alt="D" /></td>
            <td class="an3"><img src="images/C.gif" alt="C" /></td>
            <td>&nbsp;</td>
            <td class="an2">&nbsp;</td>
            <td class="an3"><img src="images/N.gif" alt="NT" /></td>
            <td class="an3"><img src="images/S.gif" alt="S" /></td>
            <td class="an3"><img src="images/H.gif" alt="H" /></td>
            <td class="an3"><img src="images/D.gif" alt="D" /></td>
            <td class="an3"><img src="images/C.gif" alt="C" /></td>
        </tr>
        <tr>
            <?php echo $this->format_ability(0); ?>
            <td>&nbsp;</td>
            <?php echo $this->format_ability(1); ?>
        </tr>
        <tr>
            <?php echo $this->format_ability(2); ?>
            <td>&nbsp;</td>
            <?php echo $this->format_ability(3); ?>
        </tr>
        </table>
        <?php echo Protocol::__("Minimax"); ?>: <?php echo $this->format_minimax(); ?>
    </td></tr>
    <?php endif; ?>
</table>

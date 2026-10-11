<?php
// What the customer is told about the consents above the checkboxes on the customer data page
?>
                        <h4>Souhlas se zpracováním osobních údajů</h4>
                        <ul>
                            <li>Zaškrtnutím níže volím, které zprávy chci od <?php echo htmlspecialchars(OPERATOR_NAME); ?> (dále jen „Správce“) dostávat na své kontaktní údaje.<br>U obchodních sdělení tím Správci zároveň dávám souhlas s jejich zasíláním a se zpracováním svých kontaktních údajů pro tento účel.</li>
                            <li>Volbu i souhlas mohu kdykoli změnit nebo odvolat, a to zde v Uživatelském portálu nebo prostřednictvím kontaktních údajů Správce. Odvolání nemá vliv na zákonnost zpracování před ním.</li>
<?php if (PRIVACY_POLICY_URL !== '') { ?>
                            <li>Jak Správce zpracovává osobní údaje, popisují <a href="<?php echo htmlspecialchars(PRIVACY_POLICY_URL); ?>" target="_blank">Zásady zpracování osobních údajů</a>.</li>
<?php } ?>
                        </ul>

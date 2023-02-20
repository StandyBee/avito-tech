<?php

namespace App\Enums;

enum TransactionType: string {
    case Add = 'add';
    case WriteOff = 'write_off';
    case SendTo = 'send_to';
}

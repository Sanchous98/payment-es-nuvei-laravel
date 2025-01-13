<?php

namespace PaymentSystem\Laravel\Nuvei\Gateway;

readonly class Transaction implements \JsonSerializable
{
    /**
     * @param array{
     *  internalRequestId: integer,
     *  status: "SUCCESS"|"ERROR",
     *  errCode: integer,
     *  merchantId: string,
     *  merchantSiteId: string,
     *  version: "1.0",
     *  userDetails: array{
     *   userTokenId: string
     *  },
     *  deviceDetails: array{
     *   ipAddress: string
     *  },
     *  transactionDetails: array{
     *   date: string,
     *   originalTransactionDate: string,
     *   transactionStatus: "Approved"|"Declined"|"Error",
     *   transactionType: "Auth"|"Settle"|"Void"|"Refund",
     *   authCode: string,
     *   credited: "True"|"False",
     *   transactionId: string,
     *   acquiringBankName: string
     *  },
     *  paymentOption: array{
     *   userPaymentOptionId: string,
     *   card: array{
     *    issuerBankName: string,
     *    issuerCountry: string,
     *    isPrepaid: "false",
     *    ccCardNumber: string,
     *    bin: string,
     *    ccExpMonth: string,
     *    ccExpYear: string,
     *    cardType: "Credit"|"Debit",
     *    cardBrand: "Visa"|"Mastercard"|"Amex",
     *    threeD: array{
     *        "whiteListStatus": "true"|"false",
     *        "isLiabilityOnIssuer": "True"|"False",
     *        "isExemptionRequestInAuthentication": "true"|"false",
     *        "exemptionRequest": "No"
     *    },
     *    cardHolderName: string
     *   }
     *  },
     *  partialApproval: array{
     *   requestedAmount: string,
     *   requestedCurrency: string
     *  },
     *  productDetails:array{
     *   productId: "NA"
     *  },
     *  fraudDetails:array{
     *   finalDecision: "Accept"|"Reject"
     *  }
     * } $data
     */
    public function __construct(
        public array $data,
        public ?Transaction $previous = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'prev' => $this->previous,
        ];
    }
}
<?php

namespace App\Command;

use App\Service\CreateJsonFileService;
use App\Service\OfferListService;
use App\Service\PromoCodeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PromoCodeCommand extends Command
{
    protected static $defaultName = 'promo-code:validate';
    protected static $defaultDescription = 'Create a JSON file with all products related to your promo code';

    private $promoCodeService;
    private $offerListService;
    private $jsonFileService;

    /**
     * @param PromoCodeService $promoCodeService
     * @param OfferListService $offerListService
     * @param CreateJsonFileService $jsonFileService
     */
    public function __construct(PromoCodeService $promoCodeService,
                                OfferListService $offerListService,
                                CreateJsonFileService $jsonFileService)
    {
        $this->promoCodeService = $promoCodeService;
        $this->offerListService = $offerListService;
        $this->jsonFileService = $jsonFileService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp("To use this command, please add as parameter your promo code. \n
        For example : php bin/console promo-code:validate <PROMO-CODE>")
            ->addArgument('promo-code', InputArgument::OPTIONAL, "promo-code");
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $promoCode = $input->getArgument('promo-code');

        $io->title("CODE PROMO VERIFICATION");

        if (is_null($promoCode)) {
            $promoCode = $io->ask('Please type your promo code', null, function ($promoCode) {
                if (empty($promoCode)) {
                    throw new \RuntimeException('You must type a promo code.');
                }
                return $promoCode;
            });
        }

        // Check validity of promo code
        $isAValidCode = $this->promoCodeService->checkCodeValidity($promoCode);

        if($isAValidCode) {
            $io->info('Your code : ' . $promoCode . ' is valid.');
            $io->info('Your file will be created in few seconds...');
            $io->progressStart(4);

            // Build list of offers related to the promo code
            $offerListDetails = $this->offerListService->getOfferListByPromoCode($promoCode);
            $io->progressAdvance(1);

            // get promo code details
            $promoCodeDetails = $this->promoCodeService->getPromoCodeDetails($promoCode);
            $io->progressAdvance(1);

            // create a list of offers related to the promo code as json
            $listCompatibleOffers = $this->promoCodeService->formatCompatibleOfferList($promoCodeDetails, $offerListDetails);
            $io->progressAdvance(1);

            // save output as json file
            $filename = $promoCode . ".json";
            $file_path = $this->jsonFileService->saveResultAsJsonFile($filename, $listCompatibleOffers);

            $io->progressFinish();
        } else {
            $io->getErrorStyle()->error('Your promo code : ' . $promoCode . ' is not valid or outdated.');
            return Command::FAILURE;
        }

        $io->success('Your json file have been successfully created in : ' . $file_path);
        return Command::SUCCESS;
    }
}
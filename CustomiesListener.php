<?php

namespace CustomiesE;

use pmmp\encoding\BE;

use CustomiesE\Blocks\CustomiesBlockFactory;
use CustomiesE\Items\CustomiesItemFactory;
use emeraude\faction\Blocks\CustomBlock;
use emeraude\faction\CustomPlayer;
use emeraude\faction\Items\CustomItem;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\crafting\CraftingManager;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\FurnaceType;
use pocketmine\crafting\RecipeIngredient;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\crafting\ShapelessRecipeType;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\cache\CraftingDataCache;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\CommandBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\recipe\CraftingRecipeBlockName;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipe as ProtocolFurnaceRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipeBlockName;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\PotionContainerChangeRecipe as ProtocolPotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionTypeRecipe as ProtocolPotionTypeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeUnlockingRequirement;
use pocketmine\network\mcpe\protocol\types\recipe\ShapedRecipe as ProtocolShapedRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapelessRecipe as ProtocolShapelessRecipe;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Binary;
use Ramsey\Uuid\Uuid;
use ReflectionException;

class CustomiesListener implements Listener
{

    private array $cachedItemTable = [];
    private bool $entries = false;
    private array $cachedBlockPalette = [];
    public Experiments $experiments;

    public function __construct()
    {
        $this->experiments = new Experiments([
            "data_driven_items" => true,
        ], true);
    }

    /** @throws ReflectionException */
    #[EventAttribute(EventPriority::MONITOR)]
    public function onDataPacketSend(DataPacketSendEvent $event): void
    {
        $packets = $event->getPackets();

        foreach ($packets as $index => $packet) {
            if ($packet instanceof StartGamePacket) {
                $packet->blockPalette = array_merge($packet->blockPalette, CustomiesBlockFactory::getInstance()->getBlockPaletteEntries());
                $packet->levelSettings->experiments = $this->experiments;

            } elseif ($packet instanceof ResourcePackStackPacket) {
                $packet->experiments = new Experiments([
                    "data_driven_items" => true,
                ], true);;

            } elseif ($packet instanceof ItemRegistryPacket) {
                $entries = (new \ReflectionClass($packet))->getProperty("entries");
                $value = $entries->getValue($packet);

                $entries->setValue($packet, array_merge(
                    $value,
                    CustomiesItemFactory::getInstance()->getItemTableEntries()
                ));

            } elseif ($packet instanceof CraftingDataPacket) {
                $packets[$index] = $this->getCache(Server::getInstance()->getCraftingManager());
            }
        }

        $event->setPackets($packets);
    }

    private array $caches = [];

    public function getCache(CraftingManager $symplyManager) : CraftingDataPacket
    {
        $manager = $symplyManager;
        $id = spl_object_id($manager);
        if (!isset($this->caches[$id])) {
            $manager->getDestructorCallbacks()->add(function () use ($id) : void {
                unset($this->caches[$id]);
            });
            $manager->getRecipeRegisteredCallbacks()->add(function () use ($id) : void {
                unset($this->caches[$id]);
            });
            $this->caches[$id] = $this->buildCraftingDataCache($symplyManager);
        }
        return $this->caches[$id];
    }

    private function buildCraftingDataCache(CraftingManager $symplyManager) : CraftingDataPacket
    {
        Timings::$craftingDataCacheRebuild->startTiming();

        $nullUUID = Uuid::fromString(Uuid::NIL);
        $converter = TypeConverter::getInstance();
        $recipesWithTypeIds = [];
        $manager = $symplyManager;

        foreach ($manager->getCraftingRecipeIndex() as $index => $recipe) {
            $recipeNetId = $index + CraftingDataCache::RECIPE_ID_OFFSET;

            if ($recipe instanceof ShapelessRecipe) {
                $typeTag = match ($recipe->getType()) {
                    ShapelessRecipeType::CRAFTING => CraftingRecipeBlockName::CRAFTING_TABLE,
                    ShapelessRecipeType::STONECUTTER => CraftingRecipeBlockName::STONECUTTER,
                    ShapelessRecipeType::CARTOGRAPHY => CraftingRecipeBlockName::CARTOGRAPHY_TABLE,
                    ShapelessRecipeType::SMITHING => CraftingRecipeBlockName::SMITHING_TABLE,
                };
                $recipesWithTypeIds[] = new ProtocolShapelessRecipe(
                    CraftingDataPacket::ENTRY_SHAPELESS,
                    Binary::writeInt($recipeNetId),
                    array_map($converter->coreRecipeIngredientToNet(...), $recipe->getIngredientList()),
                    array_map($converter->coreItemStackToNet(...), $recipe->getResults()),
                    $nullUUID,
                    $typeTag,
                    50,
                    new RecipeUnlockingRequirement(null),
                    $recipeNetId
                );
            } elseif ($recipe instanceof ShapedRecipe) {
                $inputs = [];
                for ($row = 0, $height = $recipe->getHeight(); $row < $height; ++$row) {
                    for ($column = 0, $width = $recipe->getWidth(); $column < $width; ++$column) {
                        $ingredient = $recipe->getIngredient($column, $row);
                        $inputs[$row][$column] = $converter->coreRecipeIngredientToNet($ingredient);
                    }
                }

                $items = [];
                foreach ($recipe->getResults() as $item) {
                    if ($item instanceof Block) {
                        $items[] = $item->asItem();
                    } else {
                        $items[] = $item;
                    }
                }

                $recipesWithTypeIds[] = new ProtocolShapedRecipe(
                    CraftingDataPacket::ENTRY_SHAPED,
                    Binary::writeInt($recipeNetId),
                    $inputs,
                    array_map($converter->coreItemStackToNet(...), $items),
                    $nullUUID,
                    CraftingRecipeBlockName::CRAFTING_TABLE,
                    50,
                    true,
                    new RecipeUnlockingRequirement(null),
                    $recipeNetId
                );
            } else {
                //TODO: probably special recipe types
            }
        }

        foreach (FurnaceType::cases() as $furnaceType) {
            $typeTag = match ($furnaceType) {
                FurnaceType::FURNACE => FurnaceRecipeBlockName::FURNACE,
                FurnaceType::BLAST_FURNACE => FurnaceRecipeBlockName::BLAST_FURNACE,
                FurnaceType::SMOKER => FurnaceRecipeBlockName::SMOKER,
                FurnaceType::CAMPFIRE => FurnaceRecipeBlockName::CAMPFIRE,
                FurnaceType::SOUL_CAMPFIRE => FurnaceRecipeBlockName::SOUL_CAMPFIRE
            };
            foreach ($manager->getFurnaceRecipeManager($furnaceType)->getAll() as $recipe) {
                $input = $converter->coreRecipeIngredientToNet($recipe->getInput())->getDescriptor();
                if (!$input instanceof IntIdMetaItemDescriptor) {
                    throw new AssumptionFailedError();
                }
                $recipesWithTypeIds[] = new ProtocolFurnaceRecipe(
                    CraftingDataPacket::ENTRY_FURNACE_DATA,
                    $input->getId(),
                    $input->getMeta(),
                    $converter->coreItemStackToNet($recipe->getResult()),
                    $typeTag
                );
            }
        }

        $potionTypeRecipes = [];
        foreach ($manager->getPotionTypeRecipes() as $recipe) {
            $input = $converter->coreRecipeIngredientToNet($recipe->getInput())->getDescriptor();
            $ingredient = $converter->coreRecipeIngredientToNet($recipe->getIngredient())->getDescriptor();
            if (!$input instanceof IntIdMetaItemDescriptor || !$ingredient instanceof IntIdMetaItemDescriptor) {
                throw new AssumptionFailedError();
            }
            $output = $converter->coreItemStackToNet($recipe->getOutput());
            $potionTypeRecipes[] = new ProtocolPotionTypeRecipe(
                $input->getId(),
                $input->getMeta(),
                $ingredient->getId(),
                $ingredient->getMeta(),
                $output->getId(),
                $output->getMeta()
            );
        }

        $potionContainerChangeRecipes = [];
        $itemTypeDictionary = $converter->getItemTypeDictionary();
        foreach ($manager->getPotionContainerChangeRecipes() as $recipe) {
            $input = $itemTypeDictionary->fromStringId($recipe->getInputItemId());
            $ingredient = $converter->coreRecipeIngredientToNet($recipe->getIngredient())->getDescriptor();
            if (!$ingredient instanceof IntIdMetaItemDescriptor) {
                throw new AssumptionFailedError();
            }
            $output = $itemTypeDictionary->fromStringId($recipe->getOutputItemId());
            $potionContainerChangeRecipes[] = new ProtocolPotionContainerChangeRecipe(
                $input,
                $ingredient->getId(),
                $output
            );
        }

        Timings::$craftingDataCacheRebuild->stopTiming();
        return CraftingDataPacket::create($recipesWithTypeIds, $potionTypeRecipes, $potionContainerChangeRecipes, [], true);
    }

    public function onPlayerCreation(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(CustomPlayer::class);
    }
}
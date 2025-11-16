<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Auto-populate features from category after product creation
     */
    protected function afterCreate(): void
    {
        $product = $this->record;
        
        if (!$product->category_id) {
            return;
        }
        
        $category = $product->category()->with('categoryFeatures')->first();
        
        if (!$category || $category->categoryFeatures->isEmpty()) {
            return;
        }
        
        // Create product features from category templates
        foreach ($category->categoryFeatures as $template) {
            $product->features()->create([
                'feature_name' => $template->feature_name,
                'feature_value' => $template->default_value ?? '',
                'unit' => $template->unit,
                'sort_order' => $template->sort_order,
            ]);
        }
        
        Notification::make()
            ->title('Features auto-populated')
            ->body("Added {$category->categoryFeatures->count()} features from {$category->name}. You can edit them in the Features tab.")
            ->success()
            ->duration(5000)
            ->send();
    }

    /**
     * Redirect to edit page after creation to show features tab
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}

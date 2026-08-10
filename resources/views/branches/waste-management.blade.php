<x-layouts.public :title="$title" :description="$intro">
    <article>
        <header class="bg-surface">
            <div class="mx-auto max-w-3xl px-4 py-12">
                <p class="text-sm font-semibold uppercase text-brand">Department Branch</p>
                <h1 class="mt-2 text-3xl font-bold text-brand sm:text-4xl">{{ $title }}</h1>
                <p class="mt-4 text-lg">{{ $intro }}</p>
            </div>
        </header>

        <img src="{{ $image }}" alt="" class="h-64 w-full object-cover sm:h-96" loading="lazy">

        <section class="mx-auto max-w-7xl px-4 py-12">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded border border-black/10 bg-white p-5">
                    <p class="text-sm font-semibold uppercase text-brand">Email</p>
                    <a href="mailto:{{ $contact['email'] }}" class="mt-2 block font-semibold text-ink hover:text-brand hover:underline">{{ $contact['email'] }}</a>
                </div>
                <div class="rounded border border-black/10 bg-white p-5">
                    <p class="text-sm font-semibold uppercase text-brand">Phone</p>
                    <a href="tel:{{ str_replace(' ', '', $contact['phone']) }}" class="mt-2 block font-semibold text-ink hover:text-brand hover:underline">{{ $contact['phone'] }}</a>
                </div>
                <div class="rounded border border-black/10 bg-white p-5">
                    <p class="text-sm font-semibold uppercase text-brand">Opening Hours</p>
                    <p class="mt-2 font-semibold text-ink">{{ $contact['hours'] }}</p>
                </div>
                <div class="rounded border border-black/10 bg-white p-5">
                    <p class="text-sm font-semibold uppercase text-brand">Address</p>
                    <p class="mt-2 font-semibold text-ink">{{ $contact['address'] }}</p>
                </div>
            </div>
        </section>

        <section class="bg-white py-12">
            <div class="mx-auto max-w-7xl px-4">
                <h2 class="text-2xl font-bold text-brand">Residential Waste and Recycling</h2>
                <p class="mt-3 max-w-3xl text-text">Waste separation starts at home. Households are encouraged to keep recyclables clean and ready for collection, and to arrange special pickups for bulky or hazardous items.</p>

                <div class="mt-8 grid gap-6 lg:grid-cols-2">
                    <x-waste.table-card title="Collection Days">
                        <x-waste.data-table :headers="['Service', 'Days', 'Advice']">
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink">Residential and business waste</td>
                                <td class="px-4 py-3">Mondays and Thursdays</td>
                                <td class="px-4 py-3">Put bins out early in the morning.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink">Recycling collection</td>
                                <td class="px-4 py-3">Wednesdays every fortnight</td>
                                <td class="px-4 py-3">Put recycling bins out the night before collection.</td>
                            </tr>
                        </x-waste.data-table>
                    </x-waste.table-card>

                    <x-waste.table-card title="Special Pickups">
                        <p class="text-text">Bookings are recommended for end-of-life batteries, e-waste, whiteware and large household items.</p>
                    </x-waste.table-card>
                </div>

                <div class="mt-8 grid gap-6 lg:grid-cols-2">
                    <x-waste.table-card title="Residential Recycling Bin">
                        <x-waste.image-strip :images="[
                            ['src' => 'images/waste-management/plastic-bottles.jpg', 'alt' => 'Clean plastic bottles for recycling'],
                            ['src' => 'images/waste-management/glass-bottles.jpg', 'alt' => 'Glass bottles and jars for recycling'],
                            ['src' => 'images/waste-management/tin-cans.jpg', 'alt' => 'Tin food cans for recycling'],
                        ]" />
                        <div class="mt-5">
                            <x-waste.data-table :headers="['Accepted', 'Not Accepted']">
                                <tr>
                                    <td class="px-4 py-3">Empty, clean plastic bottles and uncrushed cans.</td>
                                    <td class="px-4 py-3">Plastic wrap, bubble wrap, zip lock bags and freezer bags.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">Empty glass bottles and rinsed jars.</td>
                                    <td class="px-4 py-3">Dirty diapers and pet waste.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">Rinsed tin food cans.</td>
                                    <td class="px-4 py-3">Car parts, scrap metal, tyres, filters, propane cylinders and safety hazards.</td>
                                </tr>
                            </x-waste.data-table>
                        </div>
                    </x-waste.table-card>

                    <x-waste.table-card title="Other Waste Pickup">
                        <x-waste.image-strip :images="[
                            ['src' => 'images/waste-management/gas-bottle.jpg', 'alt' => 'Gas bottle and hazardous item example'],
                            ['src' => 'images/waste-management/garage-waste.jpg', 'alt' => 'Bulky garage waste example'],
                            ['src' => 'images/waste-management/construction-waste.jpg', 'alt' => 'Large waste item example'],
                        ]" />
                        <div class="mt-5">
                            <x-waste.data-table :headers="['Waste Type', 'Action']">
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ink">End-of-life batteries</td>
                                    <td class="px-4 py-3">Make a booking.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ink">E-waste</td>
                                    <td class="px-4 py-3">Make a booking.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ink">Whiteware and large items</td>
                                    <td class="px-4 py-3">Make a booking before collection.</td>
                                </tr>
                            </x-waste.data-table>
                        </div>
                    </x-waste.table-card>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-12">
            <div class="grid gap-6 lg:grid-cols-2">
                <x-waste.table-card title="Services for Business" intro="Businesses can use reliable waste collection and recycling drop-off services. The recycling drop-off area is open Monday to Friday, 9:00am to 4:00pm.">
                    <x-waste.image-strip :images="[
                        ['src' => 'images/waste-management/business-plastic-bottles.jpg', 'alt' => 'Plastic bottles and cans accepted at the recycling centre'],
                        ['src' => 'images/waste-management/flexible-packaging.webp', 'alt' => 'Flexible packaging not accepted at the recycling centre'],
                        ['src' => 'images/waste-management/cardboard.jpg', 'alt' => 'Flattened cardboard and paperboard accepted at the recycling centre'],
                        ['src' => 'images/waste-management/single-use-plastics.jpg', 'alt' => 'Single-use plastics not accepted at the recycling centre'],
                    ]" />
                    <div class="mt-5">
                        <x-waste.data-table :headers="['Accepted at Recycling Center', 'Not Accepted at Recycling Center']">
                            <tr>
                                <td class="px-4 py-3">Plastic bottles and cans.</td>
                                <td class="px-4 py-3">Plastic wrappings and flexible packaging.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Glass bottles and jars.</td>
                                <td class="px-4 py-3">Hard plastic food trays.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Tin metal cans.</td>
                                <td class="px-4 py-3">Paint cans.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Flattened cardboard and paperboard.</td>
                                <td class="px-4 py-3">Hygiene waste and single-use plastics.</td>
                            </tr>
                        </x-waste.data-table>
                    </div>
                </x-waste.table-card>

                <x-waste.table-card title="Schools and Public Areas" intro="Public-area bins are organised by waste stream to make sorting easier.">
                    <x-waste.image-strip :images="[
                        ['src' => 'images/waste-management/cardboard.jpg', 'alt' => 'Cardboard and paperboard for paper bins'],
                        ['src' => 'images/waste-management/takeaway-containers.jpg', 'alt' => 'Takeaway containers for general waste guidance'],
                        ['src' => 'images/waste-management/green-waste.jpg', 'alt' => 'Green waste and organic material'],
                        ['src' => 'images/waste-management/aluminium-cans.jpg', 'alt' => 'Aluminium cans for recycling bins'],
                    ]" />
                    <div class="mt-5">
                        <x-waste.data-table :headers="['Bin', 'Stream', 'Examples']">
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink">Blue</td>
                                <td class="px-4 py-3">Paper only</td>
                                <td class="px-4 py-3">Flattened cardboard and paperboard.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink">Red</td>
                                <td class="px-4 py-3">General waste</td>
                                <td class="px-4 py-3">Waxed cardboard, strapping ties, food containers and tissue paper.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink">Green</td>
                                <td class="px-4 py-3">Organic waste</td>
                                <td class="px-4 py-3">Green waste, food waste and food-soiled paper.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink">Yellow</td>
                                <td class="px-4 py-3">Plastic bottles and cans</td>
                                <td class="px-4 py-3">Drinking plastic bottles and aluminium cans.</td>
                            </tr>
                        </x-waste.data-table>
                    </div>
                </x-waste.table-card>

                <x-waste.table-card title="Sewage Disposal Charges" class="lg:col-span-2">
                    <x-waste.data-table :headers="['Days', 'Households', 'Government and business']">
                        <tr>
                            <td class="px-4 py-3">Monday to Thursday, 8:00am - 4:00pm</td>
                            <td class="px-4 py-3 font-semibold text-ink">$75.00</td>
                            <td class="px-4 py-3 font-semibold text-ink">$100.00</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3">After hours and weekends</td>
                            <td class="px-4 py-3 font-semibold text-ink">$100.00</td>
                            <td class="px-4 py-3 font-semibold text-ink">$150.00</td>
                        </tr>
                    </x-waste.data-table>
                </x-waste.table-card>
            </div>
        </section>

        <section class="bg-surface py-12">
            <div class="mx-auto max-w-7xl px-4">
                <h2 class="text-2xl font-bold text-brand">Landfill Guidance</h2>
                <p class="mt-3 max-w-3xl text-text">Makato and Vaiea landfill information is summarised here for public guidance. Recyclable materials should be directed to the recycling centre where appropriate.</p>

                <div class="mt-8 grid gap-6 lg:grid-cols-2">
                    <x-waste.table-card title="Makato Landfill" intro="Makato Landfill is located in Alofi on the lower road toward Tamakautoga village.">
                        <x-waste.image-strip :images="[
                            ['src' => 'images/waste-management/plastic-wrap.jpg', 'alt' => 'Flexible plastic packaging landfill example'],
                            ['src' => 'images/waste-management/paint-cans.jpg', 'alt' => 'Paint cans landfill handling example'],
                            ['src' => 'images/waste-management/dirty-diapers.jpg', 'alt' => 'Hygiene waste landfill example'],
                        ]" />
                        <div class="mt-5">
                            <x-waste.data-table :headers="['Material', 'Guidance']">
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ink">Plastic wrappings and flexible packaging</td>
                                    <td class="px-4 py-3">General rubbish.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ink">Hard plastic food trays</td>
                                    <td class="px-4 py-3">Accepted at landfill.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ink">Paint tins and spray cans</td>
                                    <td class="px-4 py-3">Accepted at landfill with appropriate handling.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ink">Hygiene waste</td>
                                    <td class="px-4 py-3">Accepted as dirty diapers and other unhygienic items.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ink">Bottles, jars, cans and cardboard</td>
                                    <td class="px-4 py-3">Drop off at the recycling centre.</td>
                                </tr>
                            </x-waste.data-table>
                        </div>
                    </x-waste.table-card>

                    <x-waste.table-card title="Vaiea Landfill" intro="Vaiea Landfill is located on the southern side of Niue behind Vaiea village and remains monitored by the Department.">
                        <x-waste.image-strip :images="[
                            ['src' => 'images/waste-management/takeaway-containers.jpg', 'alt' => 'Hard plastic food tray landfill example'],
                            ['src' => 'images/waste-management/paint-cans.jpg', 'alt' => 'Paint cans landfill example'],
                            ['src' => 'images/waste-management/glass-bottles.jpg', 'alt' => 'Glass bottles redirected to recycling centre'],
                        ]" />
                        <div class="mt-5">
                            <x-waste.data-table :headers="['Accepted', 'Not Accepted / Redirect']">
                                <tr>
                                    <td class="px-4 py-3">Plastic wrappings and flexible packaging.</td>
                                    <td class="px-4 py-3">Animal waste requires notification to the landfill manager.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">Hard plastic food trays.</td>
                                    <td class="px-4 py-3">Plastic bottles and cans should go to the recycling centre.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">Paint cans.</td>
                                    <td class="px-4 py-3">Glass bottles and jars should go to the recycling centre.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">Hygiene waste.</td>
                                    <td class="px-4 py-3">Tin cans, cardboard and paperboard should go to the recycling centre.</td>
                                </tr>
                            </x-waste.data-table>
                        </div>
                    </x-waste.table-card>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-3xl px-4 py-10 text-sm text-text/70">
            <p>Service information adapted for this demo from Waste Management Niue. Game, video and team-profile content has not been included.</p>
            <p class="mt-2"><a href="https://niuewastemanagement.nu/" class="font-semibold text-brand hover:underline">Visit Waste Management Niue</a></p>
        </section>
    </article>
</x-layouts.public>

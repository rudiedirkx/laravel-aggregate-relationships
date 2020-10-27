Aggregate relationships in Laravel
====

What? - Laravel already has relationships
----

Laravel can make relationships to other models, but not to scalars (e.g. numbers). This package can `COUNT()` and
`SUM()` relationships. Especially `COUNT()` is useful, because duh.

Why? - Laravel has this!
----

No. The only way in Laravel to count a relationship is with `withCount()`, which...

- only work for **eager** loading, not lazy
- can't be called after the objects exists, on the collection (like `->load('name')`)
- are always named `{name}_count`

How?
----

Add the trait to your models/base model:

	use RelatesToAggregates;

This exposes methods `hasCount(relatedClass, foreignKey)`, `hasAggregate(relatedClass, aggregateColumn, foreignKey)` and `hasManyScalar(targetKey, targetTable, foreignKey)`:

Define the relationship:

	class User extends Model {
		use RelatesToAggregates;
		
		// Number of transactions for this user
		function num_transactions() {
			return $this->hasCount(Transaction::class, 'user_id');
		}
		
		// Sum of all positive and negative transactions for this user
		function current_balance() {
			return $this->hasAggregate(Transaction::class, 'sum(money_change)', 'user_id');
		}
		
		// List of payment uuids
		function payment_refs() {
			return $this->hasManyScalar('reference_uuid', 'transactions', 'user_id');
		}
	}

And then eager load it **like normal relationships**:

	$users = User::with(['address', 'fav_color', 'num_transactions', 'current_balance', 'payment_refs'])->get();

or later, **like normal relationships**, unlike Laravel's count:

	$users = User::all();
	$users->load(['address', 'fav_color', 'num_transactions', 'current_balance', 'payment_refs']);

and if you can't/don't want to eager load it, lazy load it, **like a normal relationship**:

	$user = User::find(123);
	echo "Balance: {$user->current_balance} ({$user->num_transactions} transactions): " .
		implode(', ', $user->payment_refs);

Recap
----

- Eager AND lazy loading
- Eager loadable on existing collection
- Custom names

**Like a real relationship!**
